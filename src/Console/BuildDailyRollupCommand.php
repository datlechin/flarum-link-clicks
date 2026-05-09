<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Console;

use Carbon\CarbonImmutable;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Aggregates `link_click_events` into `link_click_daily` so analytics queries
 * over wide date ranges hit a small pre-summarised table instead of millions
 * of raw event rows.
 *
 * State: a single setting `daily_rollup_last_date` records the most recent
 * fully-rolled-up day. Each run picks up where the last left off and walks
 * forward day by day until yesterday; today is intentionally skipped because
 * its count is still moving.
 *
 * The first run (no setting) backfills from the oldest counted event. With
 * --rebuild, every existing rollup row is dropped first so a corrupted or
 * partially-populated table can be rebuilt from raw events.
 */
class BuildDailyRollupCommand extends Command
{
    protected $signature = 'link-clicks:build-daily-rollup {--rebuild : Wipe link_click_daily and rebuild from scratch}';

    protected $description = 'Aggregate link_click_events into the daily rollup table.';

    public function handle(ConnectionInterface $db, SettingsRepositoryInterface $settings): int
    {
        if ($this->option('rebuild')) {
            $this->line('Rebuilding rollup from scratch.');
            $db->table('link_click_daily')->delete();
            $settings->delete('datlechin-link-clicks.daily_rollup_last_date');
        }

        $startDate = $this->resolveStartDate($db, $settings);
        if ($startDate === null) {
            $this->info('No counted events yet — nothing to roll up.');
            return self::SUCCESS;
        }

        $yesterday = CarbonImmutable::yesterday();
        if ($startDate->greaterThan($yesterday)) {
            $this->info('Rollup already up to date.');
            return self::SUCCESS;
        }

        $cursor = $startDate;
        $totalDays = 0;
        $totalRows = 0;

        while ($cursor->lessThanOrEqualTo($yesterday)) {
            $totalRows += $this->rollupDay($db, $cursor);
            $totalDays++;
            $settings->set('datlechin-link-clicks.daily_rollup_last_date', $cursor->format('Y-m-d'));
            $cursor = $cursor->addDay();
        }

        $this->info("Rolled up {$totalDays} day(s), {$totalRows} row(s).");
        return self::SUCCESS;
    }

    protected function resolveStartDate(ConnectionInterface $db, SettingsRepositoryInterface $settings): ?CarbonImmutable
    {
        $last = (string) $settings->get('datlechin-link-clicks.daily_rollup_last_date', '');
        if ($last !== '') {
            try {
                return CarbonImmutable::parse($last)->addDay()->startOfDay();
            } catch (\Throwable) {
                // Setting is corrupt; fall through to first-event detection.
            }
        }

        $oldest = $db->table('link_click_events')->where('counted', true)->min('clicked_at');
        if ($oldest === null) {
            return null;
        }

        return CarbonImmutable::parse($oldest)->startOfDay();
    }

    /**
     * Aggregate one day's worth of counted events into the rollup table.
     *
     * Existing rows for that date are deleted first because a rerun (e.g.
     * after `--rebuild` or a manual reconcile) must replace any stale
     * partial sum rather than collide on the (date, post_link_id) primary
     * key. With the foreign key on post_link_id, deletes cascade naturally
     * if a post_link goes away.
     */
    protected function rollupDay(ConnectionInterface $db, CarbonImmutable $day): int
    {
        $start = $day->startOfDay()->toDateTimeString();
        $end = $day->addDay()->startOfDay()->toDateTimeString();
        $dateStr = $day->format('Y-m-d');

        return $db->transaction(function () use ($db, $dateStr, $start, $end): int {
            $db->table('link_click_daily')->where('date', $dateStr)->delete();

            $rows = $db->table('link_click_events')
                ->where('counted', true)
                ->where('clicked_at', '>=', $start)
                ->where('clicked_at', '<', $end)
                ->groupBy('post_link_id')
                ->select('post_link_id', $db->raw('COUNT(*) as cnt'))
                ->get();

            if ($rows->isEmpty()) {
                return 0;
            }

            $payload = $rows->map(fn ($r) => [
                'date' => $dateStr,
                'post_link_id' => (int) $r->post_link_id,
                'count' => (int) $r->cnt,
            ])->all();

            foreach (array_chunk($payload, 500) as $chunk) {
                $db->table('link_click_daily')->insert($chunk);
            }

            return count($payload);
        });
    }
}
