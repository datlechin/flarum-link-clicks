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

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 7×24 hour-of-week click heatmap. Always over the most recent 30 days of
 * counted events. Bucketing is done in PHP rather than via dialect-specific
 * date-extraction functions (MySQL HOUR(), Postgres EXTRACT, SQLite strftime
 * all differ).
 */
class LinkClickHeatmapController implements RequestHandlerInterface
{
    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('datlechin-link-clicks.viewAnalytics');

        $since = Carbon::now()->subDays(30);

        $cells = array_fill(0, 7, array_fill(0, 24, 0));
        $max = 0;

        $this->db->table('link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $since)
            ->orderBy('clicked_at')
            ->select('clicked_at')
            ->chunk(2000, function ($chunk) use (&$cells, &$max) {
                foreach ($chunk as $row) {
                    $when = Carbon::parse($row->clicked_at);
                    $dow = (int) $when->dayOfWeek; // 0 = Sunday
                    $hour = (int) $when->hour;
                    $cells[$dow][$hour]++;
                    if ($cells[$dow][$hour] > $max) {
                        $max = $cells[$dow][$hour];
                    }
                }
            });

        return new JsonResponse([
            'since' => $since->toIso8601String(),
            'max' => $max,
            'cells' => $cells,
        ]);
    }
}
