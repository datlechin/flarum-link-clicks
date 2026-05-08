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
use Flarum\Foundation\Config;
use Flarum\Post\Post;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class BackfillCommandTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 't', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p><URL url="https://a.test/x">a</URL></p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p><URL url="https://b.test/x">b</URL> <URL url="https://c.test/x">c</URL></p></t>'],
                ['id' => 3, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>no links here</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function it_inserts_post_links_for_every_unique_url(): void
    {
        $this->runCommand(['command' => 'link-clicks:backfill']);

        $this->assertSame(3, PostLink::query()->count());
        $this->assertEqualsCanonicalizing(
            ['https://a.test/x', 'https://b.test/x', 'https://c.test/x'],
            PostLink::query()->pluck('url')->all(),
        );
    }

    #[Test]
    public function it_is_idempotent_on_re_run(): void
    {
        $this->runCommand(['command' => 'link-clicks:backfill']);
        $countAfterFirst = PostLink::query()->count();

        $this->runCommand(['command' => 'link-clicks:backfill']);
        $this->assertSame($countAfterFirst, PostLink::query()->count());
    }

    #[Test]
    public function from_id_resumes_from_a_specific_post(): void
    {
        $this->runCommand(['command' => 'link-clicks:backfill', '--from-id' => 2]);

        $urls = PostLink::query()->pluck('url')->all();
        $this->assertNotContains('https://a.test/x', $urls);
        $this->assertContains('https://b.test/x', $urls);
    }

    #[Test]
    public function it_skips_internal_links_when_track_internal_is_off(): void
    {
        $forumHost = parse_url($this->app()->getContainer()->make(Config::class)->url(), PHP_URL_HOST);

        $this->database()->table('posts')->insert([
            'id' => 4,
            'number' => 4,
            'discussion_id' => 1,
            'created_at' => Carbon::now(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => '<t><p><URL url="https://'.$forumHost.'/d/1">internal</URL></p></t>',
        ]);

        $this->runCommand(['command' => 'link-clicks:backfill']);

        $this->assertSame(0, PostLink::query()->where('post_id', 4)->count());
    }
}
