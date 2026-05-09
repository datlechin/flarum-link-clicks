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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Daily click totals over a configurable window. Buckets in PHP rather
 * than via dialect-specific date functions so the same controller works
 * across the test matrix.
 */
class LinkClickTimeSeriesController implements RequestHandlerInterface
{
    private const ALLOWED_DAYS = [30, 60, 90];

    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('datlechin-link-clicks.viewAnalytics');

        $days = (int) Arr::get($request->getQueryParams(), 'days', 30);
        if (! in_array($days, self::ALLOWED_DAYS, strict: true)) {
            $days = 30;
        }

        $now = Carbon::now()->endOfDay();
        $since = $now->copy()->subDays($days - 1)->startOfDay();

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $buckets[$since->copy()->addDays($i)->format('Y-m-d')] = 0;
        }

        $this->db->table('link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $since)
            ->where('clicked_at', '<=', $now)
            ->orderBy('clicked_at')
            ->select('clicked_at')
            ->chunk(2000, function ($chunk) use (&$buckets) {
                foreach ($chunk as $row) {
                    $key = Carbon::parse($row->clicked_at)->format('Y-m-d');
                    if (isset($buckets[$key])) {
                        $buckets[$key]++;
                    }
                }
            });

        $points = [];
        $max = 0;
        foreach ($buckets as $date => $count) {
            $points[] = ['date' => $date, 'count' => $count];
            if ($count > $max) {
                $max = $count;
            }
        }

        return new JsonResponse([
            'days' => $days,
            'max' => $max,
            'points' => $points,
        ]);
    }
}
