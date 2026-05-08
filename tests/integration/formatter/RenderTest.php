<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Formatter;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

class RenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'author', 'email' => 'a@l.com', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 't', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p><URL url="https://example.com/page">https://example.com/page</URL></p></t>'],
            ],
            'post_links' => [
                [
                    'id' => 1,
                    'post_id' => 1,
                    'discussion_id' => 1,
                    'url' => 'https://example.com/page',
                    'url_hash' => hash('sha256', 'https://example.com/page'),
                    'is_internal' => 0,
                    'clicks_count' => 17,
                    'first_seen_at' => Carbon::now(),
                ],
            ],
        ]);
    }

    // Assertions match the s9e XML attributes our render callback mutates.
    // The test env doesn't exercise the XSL-to-HTML pass.

    #[Test]
    public function render_callback_rewrites_url_to_tracking_endpoint(): void
    {
        $rendered = $this->renderPost(1, withRequest: true);

        $this->assertStringContainsString('class="LinkClicks-link"', $rendered);
        $this->assertMatchesRegularExpression('#url="[^"]*/lcc/track\?u=[A-Za-z0-9_-]+"#', $rendered);
        $this->assertStringContainsString('data-post-id="1"', $rendered);
        $this->assertStringContainsString('data-url-id="1"', $rendered);
        $this->assertStringContainsString('data-clicks="17"', $rendered);
    }

    #[Test]
    public function email_render_with_null_request_keeps_original_url(): void
    {
        $rendered = $this->renderPost(1, withRequest: false);

        $this->assertStringNotContainsString('LinkClicks-link', $rendered);
        $this->assertStringNotContainsString('/lcc/track', $rendered);
        $this->assertStringNotContainsString('data-clicks', $rendered);
        $this->assertStringContainsString('url="https://example.com/page"', $rendered);
    }

    #[Test]
    public function disabled_extension_does_not_rewrite_link(): void
    {
        $this->setting('datlechin-link-clicks.enabled', false);

        $rendered = $this->renderPost(1, withRequest: true);

        $this->assertStringNotContainsString('LinkClicks-link', $rendered);
        $this->assertStringNotContainsString('/lcc/track', $rendered);
        $this->assertStringContainsString('url="https://example.com/page"', $rendered);
    }

    #[Test]
    public function link_with_no_post_links_row_renders_unmodified(): void
    {
        $this->database()->table('posts')->insert([
            'id' => 2,
            'number' => 2,
            'discussion_id' => 1,
            'created_at' => Carbon::now(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => '<t><p><URL url="https://orphan.test/x">https://orphan.test/x</URL></p></t>',
        ]);

        $rendered = $this->renderPost(2, withRequest: true);

        $this->assertStringContainsString('url="https://orphan.test/x"', $rendered);
        $this->assertStringNotContainsString('LinkClicks-link', $rendered);
    }

    #[Test]
    public function clicks_count_below_min_display_is_omitted(): void
    {
        $this->setting('datlechin-link-clicks.min_display_count', 50);

        $rendered = $this->renderPost(1, withRequest: true);

        $this->assertStringContainsString('LinkClicks-link', $rendered);
        $this->assertStringNotContainsString('data-clicks="17"', $rendered);
    }

    #[Test]
    public function tooltip_title_is_set_when_count_meets_min_display(): void
    {
        $rendered = $this->renderPost(1, withRequest: true);

        // Locale yml may not be wired in this test harness, so the translator
        // can return the raw key. We only assert that the attribute is set.
        $this->assertMatchesRegularExpression('#title="[^"]+"#', $rendered);
    }

    #[Test]
    public function tooltip_title_omitted_below_min_display(): void
    {
        $this->setting('datlechin-link-clicks.min_display_count', 50);

        $rendered = $this->renderPost(1, withRequest: true);

        $this->assertStringNotContainsString('title=', $rendered);
    }

    private function renderPost(int $id, bool $withRequest): string
    {
        $this->app(); // boots Eloquent's connection resolver
        $post = CommentPost::query()->findOrFail($id);

        return $post->formatContent($withRequest ? new ServerRequest() : null);
    }
}
