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
 * Aggregates counted clicks by (device_class, browser_family). Returns three
 * views of the same dataset:
 *   - devices:   bucket -> click count          (single-axis pie/bar)
 *   - browsers:  bucket -> click count          (single-axis pie/bar)
 *   - matrix:    rows of {device, browser, count}  (stacked bar)
 *
 * Reuses LinkClickStatsQuery so the filter shape (since/until/scope/tag)
 * stays consistent with the URL list and other analytics endpoints.
 */
class LinkClickDeviceBreakdownController implements RequestHandlerInterface
{
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

        $col = fn (string $c) => $this->query->col($c);
        $base = $this->query->baseQuery($filter);

        $rows = (clone $base)
            ->whereNotNull('link_click_events.device_class')
            ->selectRaw($col('link_click_events.device_class').' as device_class,
                         '.$col('link_click_events.browser_family').' as browser_family,
                         COUNT(*) as total_clicks')
            ->groupBy('link_click_events.device_class', 'link_click_events.browser_family')
            ->orderByDesc('total_clicks')
            ->get();

        $byDevice = [];
        $byBrowser = [];
        $matrix = [];
        $grandTotal = 0;

        foreach ($rows as $r) {
            $device = (string) $r->device_class;
            $browser = $r->browser_family ?: 'other';
            $count = (int) $r->total_clicks;

            $byDevice[$device] = ($byDevice[$device] ?? 0) + $count;
            $byBrowser[$browser] = ($byBrowser[$browser] ?? 0) + $count;
            $grandTotal += $count;

            $matrix[] = [
                'device_class' => $device,
                'browser_family' => $browser,
                'total_clicks' => $count,
            ];
        }

        arsort($byDevice);
        arsort($byBrowser);

        return new JsonResponse([
            'total' => $grandTotal,
            'devices' => $byDevice,
            'browsers' => $byBrowser,
            'matrix' => $matrix,
        ]);
    }
}
