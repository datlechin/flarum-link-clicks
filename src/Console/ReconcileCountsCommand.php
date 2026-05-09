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

use Datlechin\LinkClicks\PostLink;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Walks every post_link, recomputes the per-link click count from the raw
 * event log, and writes back any drift. Drift can creep in from manual SQL
 * edits, partial backfills, GDPR delete edge cases, or the occasional
 * lockForUpdate retry failing under load.
 *
 * Idempotent. Safe to schedule daily.
 */
class ReconcileCountsCommand extends Command
{
    protected $signature = 'link-clicks:reconcile {--dry-run : Report drift without writing fixes} {--chunk=500 : post_links rows per batch}';

    protected $description = 'Reconcile post_links.clicks_count with the actual counted events.';

    public function handle(ConnectionInterface $db): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $drifted = 0;

        PostLink::query()->orderBy('id')->chunk($chunkSize, function ($links) use ($db, $dryRun, &$drifted) {
            foreach ($links as $link) {
                $actual = (int) $db->table('link_click_events')
                    ->where('post_link_id', $link->id)
                    ->where('counted', true)
                    ->count();

                if ($actual === (int) $link->clicks_count) {
                    continue;
                }

                $drifted++;
                $diff = $actual - $link->clicks_count;
                $this->line(sprintf(
                    '  drift on link #%d (%s): stored=%d, actual=%d, diff=%+d',
                    $link->id,
                    $link->display_url,
                    $link->clicks_count,
                    $actual,
                    $diff
                ));

                if (! $dryRun) {
                    $link->clicks_count = $actual;
                    $link->save();
                }
            }
        });

        if ($drifted === 0) {
            $this->info('No drift detected.');
        } elseif ($dryRun) {
            $this->info("Found drift on {$drifted} link(s). Re-run without --dry-run to apply fixes.");
        } else {
            $this->info("Reconciled {$drifted} link(s).");
        }

        return self::SUCCESS;
    }
}
