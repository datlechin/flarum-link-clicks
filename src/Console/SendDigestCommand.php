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
use Flarum\Foundation\Config;
use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Mail\Mailer;
use Throwable;

/**
 * Weekly digest emailed to every administrator. Plain text on purpose:
 * keeps the email lean, sidesteps view-template plumbing, and renders
 * predictably in every mail client.
 *
 * Skipped when `digest_enabled` is off, when there are no admins with an
 * email address, or when no clicks were recorded in the period.
 */
class SendDigestCommand extends Command
{
    protected $signature = 'link-clicks:send-digest {--dry-run : Render the digest to stdout instead of sending}';

    protected $description = 'Email a weekly link-click summary to all administrators.';

    public function handle(
        ConnectionInterface $db,
        SettingsRepositoryInterface $settings,
        Mailer $mailer,
        Config $config,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! (bool) $settings->get('datlechin-link-clicks.digest_enabled', false)) {
            $this->info('Digest is disabled. Set datlechin-link-clicks.digest_enabled to true to opt in.');
            return self::SUCCESS;
        }

        $until = Carbon::now();
        $since = $until->copy()->subDays(7);

        $totalClicks = (int) $db->table('link_click_events')
            ->where('counted', true)
            ->where('clicked_at', '>=', $since)
            ->count();

        if ($totalClicks === 0) {
            $this->info('No clicks recorded in the last 7 days; nothing to send.');
            return self::SUCCESS;
        }

        $topLinks = $db->table('link_click_events')
            ->join('post_links', 'post_links.id', '=', 'link_click_events.post_link_id')
            ->where('link_click_events.counted', true)
            ->where('link_click_events.clicked_at', '>=', $since)
            ->where('post_links.is_internal', false)
            ->where('post_links.is_attachment', false)
            ->select('post_links.url')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('post_links.url')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $newLinks = (int) PostLink::query()
            ->where('first_seen_at', '>=', $since)
            ->count();

        $body = $this->renderBody(
            $config->url(),
            $since,
            $until,
            $totalClicks,
            $newLinks,
            $topLinks->all()
        );

        if ($dryRun) {
            $this->line($body);
            return self::SUCCESS;
        }

        $admins = User::query()
            ->whereExists(function ($q) {
                $q->select($q->raw(1))
                    ->from('group_user')
                    ->whereColumn('group_user.user_id', 'users.id')
                    ->where('group_user.group_id', Group::ADMINISTRATOR_ID);
            })
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No administrators with email addresses found.');
            return self::SUCCESS;
        }

        $subject = sprintf('Link clicks digest for %s', parse_url($config->url(), PHP_URL_HOST) ?: 'forum');
        $sent = 0;

        foreach ($admins as $admin) {
            try {
                $mailer->raw($body, function ($message) use ($admin, $subject) {
                    $message->to($admin->email, $admin->display_name)->subject($subject);
                });
                $sent++;
            } catch (Throwable $e) {
                $this->warn('  failed to send to '.$admin->email.': '.$e->getMessage());
            }
        }

        $this->info(sprintf('Digest sent to %d admin(s).', $sent));
        return self::SUCCESS;
    }

    /**
     * @param list<object> $topLinks
     */
    private function renderBody(string $forumUrl, Carbon $since, Carbon $until, int $totalClicks, int $newLinks, array $topLinks): string
    {
        $lines = [];
        $lines[] = 'Link clicks digest';
        $lines[] = sprintf('%s — %s', $since->format('M j'), $until->format('M j, Y'));
        $lines[] = '';
        $lines[] = sprintf('Total clicks: %s', number_format($totalClicks));
        $lines[] = sprintf('New tracked links: %s', number_format($newLinks));
        $lines[] = '';

        if ($topLinks !== []) {
            $lines[] = 'Top external links';
            $lines[] = str_repeat('-', 40);
            foreach ($topLinks as $i => $row) {
                $lines[] = sprintf('%2d. %s — %s clicks', $i + 1, $row->url, number_format((int) $row->c));
            }
            $lines[] = '';
        }

        $lines[] = sprintf('Open the analytics dashboard: %s/admin#/extension/datlechin-link-clicks', rtrim($forumUrl, '/'));
        $lines[] = '';
        $lines[] = 'Sent by datlechin/flarum-link-clicks. Disable in Admin → Extensions → Link Clicks.';

        return implode("\n", $lines);
    }
}
