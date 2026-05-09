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
 * Same dataset as the per-URL stats list but rolled up by domain. Useful
 * for "where is the forum sending traffic" at a glance: 50 unique URLs to
 * github.com collapse to one row.
 *
 * Reuses LinkClickStatsQuery so the filter shape (since/until/scope/tag)
 * stays in lockstep with the URL list and CSV export.
 */
class ListLinkClickDomainsController implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 25;
    private const MAX_LIMIT = 100;

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

        $col = fn (string $c) => $this->query->col($c);
        $base = $this->query->baseQuery($filter);

        $total = (clone $base)
            ->whereNotNull('post_links.domain')
            ->select($this->db->raw('COUNT(DISTINCT '.$col('post_links.domain').') as c'))
            ->value('c');

        $rows = (clone $base)
            ->whereNotNull('post_links.domain')
            ->selectRaw($col('post_links.domain').' as domain,
                         COUNT(DISTINCT '.$col('post_links.url_hash').') as url_count,
                         COUNT(*) as total_clicks,
                         (COUNT(DISTINCT '.$col('link_click_events.user_id').') + COUNT(DISTINCT CASE WHEN '.$col('link_click_events.user_id').' IS NULL THEN '.$col('link_click_events.ip_address').' END)) as unique_users,
                         MIN('.$col('link_click_events.clicked_at').') as first_clicked,
                         MAX('.$col('link_click_events.clicked_at').') as last_clicked')
            ->groupBy('post_links.domain')
            ->orderByDesc('total_clicks')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn (object $row) => [
            'domain' => $row->domain,
            'url_count' => (int) $row->url_count,
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
}
