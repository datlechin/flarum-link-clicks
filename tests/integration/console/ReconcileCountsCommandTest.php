<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Console;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ReconcileCountsCommandTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 't', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'],
            ],
            'post_links' => [
                // Stored 1, actual will be 3 (drift +2)
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/a', 'url_hash' => hash('sha256', 'a'), 'is_internal' => 0, 'clicks_count' => 1, 'first_seen_at' => Carbon::now()],
                // Stored 5, actual will be 2 (drift -3)
                ['id' => 2, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/b', 'url_hash' => hash('sha256', 'b'), 'is_internal' => 0, 'clicks_count' => 5, 'first_seen_at' => Carbon::now()],
                // Stored 0, actual will be 0 (clean, no drift)
                ['id' => 3, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/c', 'url_hash' => hash('sha256', 'c'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
            ],
            'link_click_events' => [
                ['id' => 1, 'post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()],
                ['id' => 2, 'post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subSecond()],
                ['id' => 3, 'post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subSeconds(2)],
                ['id' => 4, 'post_link_id' => 1, 'user_id' => 2, 'counted' => 0, 'clicked_at' => Carbon::now()->subSeconds(3)],
                ['id' => 5, 'post_link_id' => 2, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subSeconds(4)],
                ['id' => 6, 'post_link_id' => 2, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subSeconds(5)],
            ],
        ]);
    }

    #[Test]
    public function default_run_fixes_drift_in_both_directions(): void
    {
        $output = $this->runCommand(['command' => 'link-clicks:reconcile']);

        $this->assertStringContainsString('Reconciled 2 link', $output);
        $this->assertSame(3, (int) PostLink::query()->find(1)->clicks_count);
        $this->assertSame(2, (int) PostLink::query()->find(2)->clicks_count);
        $this->assertSame(0, (int) PostLink::query()->find(3)->clicks_count);
    }

    #[Test]
    public function dry_run_reports_drift_without_writing(): void
    {
        $output = $this->runCommand(['command' => 'link-clicks:reconcile', '--dry-run' => true]);

        $this->assertStringContainsString('Found drift on 2', $output);
        $this->assertSame(1, (int) PostLink::query()->find(1)->clicks_count);
        $this->assertSame(5, (int) PostLink::query()->find(2)->clicks_count);
    }

    #[Test]
    public function only_counted_events_are_summed(): void
    {
        // Link #1 has 4 events but one is counted=0 → reconcile should yield 3.
        $this->runCommand(['command' => 'link-clicks:reconcile']);

        $this->assertSame(3, (int) PostLink::query()->find(1)->clicks_count);
    }

    #[Test]
    public function reports_no_drift_when_already_consistent(): void
    {
        $this->runCommand(['command' => 'link-clicks:reconcile']);
        $output = $this->runCommand(['command' => 'link-clicks:reconcile']);

        $this->assertStringContainsString('No drift detected', $output);
    }
}
