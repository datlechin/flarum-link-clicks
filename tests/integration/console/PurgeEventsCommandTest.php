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
use Datlechin\LinkClicks\LinkClickEvent;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class PurgeEventsCommandTest extends ConsoleTestCase
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
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com', 'url_hash' => hash('sha256', 'https://example.com'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
            ],
            'link_click_events' => [
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(120)],
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(95)],
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(30)],
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subHours(2)],
            ],
        ]);
    }

    #[Test]
    public function purges_events_older_than_retention(): void
    {
        $this->setting('datlechin-link-clicks.event_retention_days', 90);

        $this->runCommand(['command' => 'link-clicks:purge-events']);

        $this->assertSame(2, LinkClickEvent::query()->count());
    }

    #[Test]
    public function noop_when_retention_zero(): void
    {
        $this->setting('datlechin-link-clicks.event_retention_days', 0);

        $this->runCommand(['command' => 'link-clicks:purge-events']);

        $this->assertSame(4, LinkClickEvent::query()->count());
    }

    #[Test]
    public function idempotent_on_re_run(): void
    {
        $this->setting('datlechin-link-clicks.event_retention_days', 90);

        $this->runCommand(['command' => 'link-clicks:purge-events']);
        $afterFirst = LinkClickEvent::query()->count();

        $this->runCommand(['command' => 'link-clicks:purge-events']);
        $afterSecond = LinkClickEvent::query()->count();

        $this->assertSame($afterFirst, $afterSecond);
    }
}
