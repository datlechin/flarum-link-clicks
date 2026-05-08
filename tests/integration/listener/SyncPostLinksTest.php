<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Listener;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\Config;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class SyncPostLinksTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
        ]);
    }

    #[Test]
    public function on_posted_creates_a_row_per_unique_link(): void
    {
        $post = $this->insertPost(1, '<t><p>Hello <URL url="https://example.com/a">https://example.com/a</URL> and <URL url="https://example.com/b">https://example.com/b</URL></p></t>');

        $this->fire(new Posted($post));

        $links = PostLink::query()->where('post_id', 1)->orderBy('url')->get();
        $this->assertCount(2, $links);
        $this->assertEqualsCanonicalizing(
            ['https://example.com/a', 'https://example.com/b'],
            $links->pluck('url')->all(),
        );
    }

    #[Test]
    public function duplicate_urls_in_one_post_collapse_to_one_row(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/x">a</URL> <URL url="https://example.com/x">b</URL></p></t>');

        $this->fire(new Posted($post));

        $this->assertSame(1, PostLink::query()->where('post_id', 1)->count());
    }

    #[Test]
    public function tracking_params_are_stripped_so_utm_variants_collapse(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/page?utm_source=tw">a</URL> <URL url="https://example.com/page?utm_source=fb">b</URL></p></t>');

        $this->fire(new Posted($post));

        $rows = PostLink::query()->where('post_id', 1)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('https://example.com/page', $rows->first()->url);
    }

    #[Test]
    public function on_revised_removes_rows_for_links_no_longer_in_post(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://a.test/x">a</URL> <URL url="https://b.test/x">b</URL></p></t>');
        $this->fire(new Posted($post));
        $this->assertSame(2, PostLink::query()->where('post_id', 1)->count());

        $this->updatePostContent(1, '<t><p>Just <URL url="https://a.test/x">a</URL></p></t>');
        $post = CommentPost::query()->findOrFail(1);
        $this->fire(new Revised($post, $this->actor(), ''));

        $remaining = PostLink::query()->where('post_id', 1)->get();
        $this->assertCount(1, $remaining);
        $this->assertSame('https://a.test/x', $remaining->first()->url);
    }

    #[Test]
    public function on_revised_with_no_links_deletes_all_rows(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://a.test/x">a</URL></p></t>');
        $this->fire(new Posted($post));
        $this->assertSame(1, PostLink::query()->where('post_id', 1)->count());

        $this->updatePostContent(1, '<t><p>No links anymore</p></t>');
        $post = CommentPost::query()->findOrFail(1);
        $this->fire(new Revised($post, $this->actor(), ''));

        $this->assertSame(0, PostLink::query()->where('post_id', 1)->count());
    }

    #[Test]
    public function existing_rows_preserve_their_first_seen_at_on_revised(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://a.test/x">a</URL></p></t>');
        $this->fire(new Posted($post));

        $original = PostLink::query()->where('post_id', 1)->first();
        $this->assertNotNull($original);
        $originalFirstSeen = $original->first_seen_at;

        sleep(1);
        $this->fire(new Revised($post, $this->actor(), ''));

        $reloaded = PostLink::query()->where('post_id', 1)->first();
        $this->assertEquals(
            $originalFirstSeen->timestamp,
            $reloaded->first_seen_at->timestamp,
        );
    }

    #[Test]
    public function internal_links_are_skipped_by_default(): void
    {
        $forumHost = parse_url($this->forumUrl(), PHP_URL_HOST);
        $internalUrl = 'https://'.$forumHost.'/d/1';

        $post = $this->insertPost(1, '<t><p><URL url="'.$internalUrl.'">a</URL> <URL url="https://external.test/x">b</URL></p></t>');

        $this->fire(new Posted($post));

        $rows = PostLink::query()->where('post_id', 1)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('https://external.test/x', $rows->first()->url);
    }

    #[Test]
    public function disabled_post_skips_link_creation_on_first_post(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/x">a</URL></p></t>');
        $this->database()->table('posts')->where('id', 1)->update(['link_clicks_disabled' => 1]);
        $reloaded = CommentPost::query()->findOrFail(1);

        $this->fire(new Posted($reloaded));

        $this->assertSame(0, PostLink::query()->where('post_id', 1)->count());
    }

    #[Test]
    public function disabled_post_preserves_existing_post_links(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/x">a</URL></p></t>');
        $this->fire(new Posted($post));
        $this->assertSame(1, PostLink::query()->where('post_id', 1)->count());

        // Toggle disabled then re-sync. Rows must NOT be deleted, so signed
        // tokens already in rendered HTML still resolve in TrackClickController.
        $this->database()->table('posts')->where('id', 1)->update(['link_clicks_disabled' => 1]);
        $reloaded = CommentPost::query()->findOrFail(1);

        $this->app()->getContainer()->make(\Datlechin\LinkClicks\Listener\SyncPostLinks::class)->sync($reloaded);

        $this->assertSame(1, PostLink::query()->where('post_id', 1)->count());
    }

    #[Test]
    public function disabled_extension_writes_no_rows(): void
    {
        $this->setting('datlechin-link-clicks.enabled', false);

        $post = $this->insertPost(1, '<t><p><URL url="https://a.test/x">a</URL></p></t>');
        $this->fire(new Posted($post));

        $this->assertSame(0, PostLink::query()->where('post_id', 1)->count());
    }

    private function insertPost(int $id, string $parsedXml): CommentPost
    {
        $this->database()->table('posts')->insert([
            'id' => $id,
            'number' => 1,
            'discussion_id' => 1,
            'created_at' => Carbon::now(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => $parsedXml,
        ]);

        return CommentPost::query()->findOrFail($id);
    }

    private function updatePostContent(int $id, string $parsedXml): void
    {
        $this->database()->table('posts')->where('id', $id)->update(['content' => $parsedXml]);
    }

    private function fire(object $event): void
    {
        $this->app()->getContainer()->make(Dispatcher::class)->dispatch($event);
    }

    private function actor(): User
    {
        return User::query()->findOrFail(2);
    }

    private function forumUrl(): string
    {
        return $this->app()->getContainer()->make(Config::class)->url();
    }
}
