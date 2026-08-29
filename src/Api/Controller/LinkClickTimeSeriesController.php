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
 * Daily click totals over a configurable window.
 *
 * Hot path: pull historical days from `link_click_daily` (one row per
 * post_link per day, pre-aggregated by the daily rollup command) and only
 * count today's still-moving events from the raw table. For a forum with
 * millions of events that's the difference between scanning a 90-day slice
 * of the events table and scanning a tiny rollup plus one day of raw rows.
 *
 * Falls back gracefully when the rollup hasn't been built yet, any day
 * missing from the rollup is filled in from the raw events table on demand.
 */
class LinkClickTimeSeriesController implements RequestHandlerInterface
{
    use ScopesBySource;

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

        $today = Carbon::now()->startOfDay();
        $since = $today->copy()->subDays($days - 1);
        $yesterday = $today->copy()->subDay();

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $buckets[$since->copy()->addDays($i)->format('Y-m-d')] = 0;
        }

        // Past days: pre-aggregated per (date, post_link_id), so summing here
        // collapses the matrix to one row per day in a single round trip.
        $rollup = $this->scopeToSource($this->db->table('link_click_daily'), $request, 'link_click_daily')
            ->where('date', '>=', $since->format('Y-m-d'))
            ->where('date', '<=', $yesterday->format('Y-m-d'))
            ->groupBy('date')
            ->select('date', $this->db->raw('SUM(count) as total'))
            ->get();

        $rolledUpDates = [];
        foreach ($rollup as $row) {
            $key = (string) $row->date;
            // Some drivers (Postgres) return a Carbon-castable date string
            // including time; normalise to the YYYY-MM-DD slot we generated
            // up front so the lookup matches.
            $key = substr($key, 0, 10);
            if (isset($buckets[$key])) {
                $buckets[$key] = (int) $row->total;
                $rolledUpDates[$key] = true;
            }
        }

        // Today is always read from raw events because the rollup deliberately
        // stops at yesterday, today's count is still moving.
        $missingDates = array_diff_key($buckets, $rolledUpDates);
        // Always include today even if rolledUpDates somehow contains it.
        $missingDates[$today->format('Y-m-d')] = 0;

        $earliestMissing = min(array_keys($missingDates));
        $rangeStart = Carbon::parse($earliestMissing)->startOfDay();
        $rangeEnd = $today->copy()->endOfDay();

        $this->scopeToSource($this->db->table('link_click_events'), $request, 'link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $rangeStart)
            ->where('clicked_at', '<=', $rangeEnd)
            ->orderBy('clicked_at')
            ->select('clicked_at')
            ->chunk(2000, function ($chunk) use (&$buckets, $missingDates) {
                foreach ($chunk as $row) {
                    $key = Carbon::parse($row->clicked_at)->format('Y-m-d');
                    if (isset($missingDates[$key]) && isset($buckets[$key])) {
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
