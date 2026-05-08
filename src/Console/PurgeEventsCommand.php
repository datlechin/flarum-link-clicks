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
use Datlechin\LinkClicks\LinkClickEvent;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;

class PurgeEventsCommand extends Command
{
    protected $signature = 'link-clicks:purge-events';

    protected $description = 'Delete link_click_events older than the configured retention window.';

    public function handle(SettingsRepositoryInterface $settings): int
    {
        $retentionDays = (int) $settings->get('datlechin-link-clicks.event_retention_days', 90);

        if ($retentionDays <= 0) {
            $this->info('Retention disabled (event_retention_days <= 0); nothing to purge.');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($retentionDays);

        $deleted = LinkClickEvent::query()
            ->where('clicked_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Purged %d link click event(s) older than %d days (cutoff %s).',
            $deleted,
            $retentionDays,
            $cutoff->toIso8601String(),
        ));

        return self::SUCCESS;
    }
}
