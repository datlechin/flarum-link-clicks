<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Api\Controller;

use Datlechin\LinkClicks\Service\LinkClickStatsQuery;
use Flarum\Http\RequestUtil;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forum-wide link analytics. Aggregates `post_links` rows by URL hash and
 * joins with `link_click_events` to compute precise click counts and unique
 * users inside the requested date range.
 */
class ListLinkClickStatsController implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 25;
    private const MAX_LIMIT = 100;
    private const ALLOWED_SORTS = ['total_clicks', 'unique_users', 'last_clicked', 'first_clicked'];

    public function __construct(
        protected ConnectionInterface $db,
        protected LinkClickStatsQuery $query,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('datlechin-link-clicks.viewAnalytics');

        $params = $request->getQueryParams();

        try {
            $filter = $this->query->parseFilter((array) Arr::get($params, 'filter', []));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        $offset = max(0, (int) Arr::get($params, 'page.offset', 0));
        $limit = min(self::MAX_LIMIT, max(1, (int) Arr::get($params, 'page.limit', self::DEFAULT_LIMIT)));

        [$sortColumn, $sortDir] = $this->parseSort((string) Arr::get($params, 'sort', '-total_clicks'));

        $base = $this->query->baseQuery($filter);

        $total = (clone $base)
            ->select($this->db->raw('COUNT(DISTINCT post_links.url_hash) as c'))
            ->value('c');

        $rows = (clone $base)
            ->selectRaw('post_links.url, post_links.url_hash,
                         MAX(post_links.is_internal) as is_internal,
                         MAX(post_links.is_attachment) as is_attachment,
                         COUNT(*) as total_clicks,
                         COUNT(DISTINCT COALESCE(CAST(link_click_events.user_id AS CHAR), link_click_events.ip_address)) as unique_users,
                         MIN(link_click_events.clicked_at) as first_clicked,
                         MAX(link_click_events.clicked_at) as last_clicked')
            ->groupBy('post_links.url_hash', 'post_links.url')
            ->orderBy($sortColumn, $sortDir)
            ->limit($limit)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn (object $row) => [
            'url' => $row->url,
            'url_hash' => $row->url_hash,
            'is_internal' => (bool) $row->is_internal,
            'is_attachment' => (bool) $row->is_attachment,
            'total_clicks' => (int) $row->total_clicks,
            'unique_users' => (int) $row->unique_users,
            'first_clicked' => $row->first_clicked,
            'last_clicked' => $row->last_clicked,
        ])->all();

        return new JsonResponse([
            'rows' => $data,
            'total' => (int) $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseSort(string $raw): array
    {
        $dir = str_starts_with($raw, '-') ? 'desc' : 'asc';
        $column = ltrim($raw, '-');

        if (! in_array($column, self::ALLOWED_SORTS, strict: true)) {
            $column = 'total_clicks';
            $dir = 'desc';
        }

        return [$column, $dir];
    }
}
