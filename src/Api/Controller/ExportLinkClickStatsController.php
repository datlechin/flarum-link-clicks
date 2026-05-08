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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CSV export of the same analytics dataset rendered in the admin table.
 *
 * Uses the same filter parsing and base query as `ListLinkClickStatsController`
 * (via `LinkClickStatsQuery`) so the export is guaranteed to match what the
 * admin sees on screen, just unpaginated and ordered by total_clicks desc.
 *
 * CSV is written to `php://temp` which spills to disk after 2 MB, so memory
 * stays bounded even on forums with tens of thousands of unique URLs.
 */
class ExportLinkClickStatsController implements RequestHandlerInterface
{
    public function __construct(
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

        $col = fn (string $c) => $this->query->col($c);

        $rows = $this->query->baseQuery($filter)
            ->selectRaw($col('post_links.url').', MAX('.$col('post_links.is_internal').') as is_internal,
                         COUNT(*) as total_clicks,
                         COUNT(DISTINCT COALESCE(CAST('.$col('link_click_events.user_id').' AS CHAR), '.$col('link_click_events.ip_address').')) as unique_users,
                         MIN('.$col('link_click_events.clicked_at').') as first_clicked,
                         MAX('.$col('link_click_events.clicked_at').') as last_clicked')
            ->groupBy('post_links.url_hash', 'post_links.url')
            ->orderBy('total_clicks', 'desc')
            ->cursor();

        $handle = fopen('php://temp', 'r+b');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open temporary stream for CSV export.');
        }

        // BOM so Excel opens UTF-8 URLs/dates correctly on Windows.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['url', 'is_internal', 'total_clicks', 'unique_users', 'first_clicked', 'last_clicked']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->url,
                (int) $row->is_internal === 1 ? 'true' : 'false',
                (int) $row->total_clicks,
                (int) $row->unique_users,
                $row->first_clicked,
                $row->last_clicked,
            ]);
        }

        rewind($handle);

        return (new Response(new Stream($handle)))
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="link-clicks-'.date('Y-m-d').'.csv"');
    }
}
