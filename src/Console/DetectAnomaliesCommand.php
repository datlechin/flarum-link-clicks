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

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Flags links whose last-24-hour click rate is far above their preceding
 * 6-day average. Writes structured warnings to the logger so admins can
 * route alerts (email, Slack, webhook) through their own log infrastructure
 * without us shipping a notification stack.
 *
 * Skips links below `anomaly_min_clicks` to avoid noise from sparse traffic
 * (a 0 → 5 jump is a 5x ratio but rarely interesting).
 */
class DetectAnomaliesCommand extends Command
{
    protected $signature = 'link-clicks:detect-anomalies {--dry-run : Print findings without logging}';

    protected $description = 'Detect post_links whose last-24h click rate is anomalously high.';

    public function handle(
        ConnectionInterface $db,
        SettingsRepositoryInterface $settings,
        LoggerInterface $logger,
    ): int {
        $threshold = (float) $settings->get('datlechin-link-clicks.anomaly_threshold_ratio', 10);
        $minClicks = (int) $settings->get('datlechin-link-clicks.anomaly_min_clicks', 20);
        $dryRun = (bool) $this->option('dry-run');

        if ($threshold <= 1) {
            $this->error('anomaly_threshold_ratio must be > 1.');
            return self::FAILURE;
        }

        $now = Carbon::now();
        $last24hStart = $now->copy()->subDay();
        $baselineStart = $now->copy()->subDays(7);

        // Two cheap queries instead of a CASE-WHEN aggregate that runs into
        // the same dialect issues we saw on the unique-actor count.
        $lastDay = $db->table('link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $last24hStart)
            ->select('post_link_id')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('post_link_id')
            ->pluck('c', 'post_link_id');

        $priorWindow = $db->table('link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $baselineStart)
            ->where('clicked_at', '<', $last24hStart)
            ->select('post_link_id')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('post_link_id')
            ->pluck('c', 'post_link_id');

        $found = 0;

        foreach ($lastDay as $postLinkId => $todayCount) {
            $today = (int) $todayCount;
            if ($today < $minClicks) {
                continue;
            }

            // Baseline = avg per-day clicks over the prior 6-day window.
            $priorTotal = (int) ($priorWindow[$postLinkId] ?? 0);
            $baseline = max(1.0, $priorTotal / 6);
            $ratio = $today / $baseline;

            if ($ratio < $threshold) {
                continue;
            }

            $found++;
            $link = PostLink::query()->find($postLinkId);
            $url = $link === null ? '(deleted)' : $link->url;

            $this->warn(sprintf(
                '  spike on link #%d (%s): today=%d, baseline=%.1f/day, ratio=%.1fx',
                $postLinkId,
                $url,
                $today,
                $baseline,
                $ratio
            ));

            if (! $dryRun) {
                $logger->warning('Link clicks anomaly detected', [
                    'post_link_id' => $postLinkId,
                    'url' => $url,
                    'last_24h_count' => $today,
                    'baseline_per_day' => round($baseline, 2),
                    'ratio' => round($ratio, 2),
                    'detected_at' => $now->toIso8601String(),
                ]);
            }
        }

        if ($found === 0) {
            $this->info('No anomalies detected in the last 24 hours.');
        } else {
            $this->info(sprintf('%d anomal%s flagged.', $found, $found === 1 ? 'y' : 'ies'));
        }

        return self::SUCCESS;
    }
}
