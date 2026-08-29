<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Source;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * `#hashtag` mentions, end to end: extraction keyed on the tag's id, and a
 * render pass that attaches a badge without disturbing the pill flarum/tags
 * styles.
 */
class TagMentionSourceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-mentions', 'datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Support', 'slug' => 'support', 'position' => 0],
            ],
        ]);
    }

    #[Test]
    public function a_hashtag_creates_a_row_keyed_on_the_tags_id(): void
    {
        $post = $this->insertPost('<t><p>See <TAGMENTION id="1" slug="support" tagname="Support">#support</TAGMENTION></p></t>');

        $this->fire(new Posted($post));

        $link = PostLink::query()->where('source', PostLink::SOURCE_TAG_MENTION)->firstOrFail();

        $this->assertSame(1, $link->source_id);
        $this->assertSame(hash('sha256', 'tag_mention:1'), $link->url_hash);
        $this->assertSame('#Support', $link->label);
        $this->assertTrue($link->is_internal);
    }

    /**
     * The whole reason identity is the tag id rather than its URL: renaming a
     * tag rewrites the link on every mention already written, and a URL-keyed
     * row would start a second, empty history at each rename.
     */
    #[Test]
    public function renaming_a_tag_keeps_the_same_row_and_refreshes_its_display(): void
    {
        $post = $this->insertPost('<t><p><TAGMENTION id="1" slug="support" tagname="Support">#support</TAGMENTION></p></t>');
        $this->fire(new Posted($post));

        $original = PostLink::query()->where('source', PostLink::SOURCE_TAG_MENTION)->firstOrFail();

        $this->database()->table('tags')->where('id', 1)->update(['name' => 'Help', 'slug' => 'help']);
        $this->fire(new Posted(CommentPost::query()->findOrFail(1)));

        $rows = PostLink::query()->where('source', PostLink::SOURCE_TAG_MENTION)->get();

        $this->assertCount(1, $rows, 'a rename must not fork the click history');
        $this->assertSame($original->id, $rows->first()->id);
        $this->assertSame('#Help', $rows->first()->label);
        $this->assertStringEndsWith('/t/help', $rows->first()->url);
    }

    #[Test]
    public function render_attaches_a_badge_without_disturbing_the_tag_pill(): void
    {
        $post = $this->insertPost('<t><p><TAGMENTION id="1" slug="support" tagname="Support">#support</TAGMENTION></p></t>');
        $this->fire(new Posted($post));

        PostLink::query()->where('source', PostLink::SOURCE_TAG_MENTION)->update(['clicks_count' => 9]);

        $rendered = CommentPost::query()->findOrFail(1)
            ->formatContent(new \Laminas\Diactoros\ServerRequest());

        $this->assertStringContainsString('data-clicks="9"', $rendered);
        $this->assertMatchesRegularExpression('#data-lc-href="[^"]*/lcc/track\?u=[A-Za-z0-9_-]+"#', $rendered);
        // The source deliberately never forwards `class`, so the template's own
        // TagMention classes survive untouched.
        $this->assertStringNotContainsString('LinkClicks-link', $rendered);
    }

    #[Test]
    public function a_setting_turns_hashtag_tracking_off(): void
    {
        $this->setting('datlechin-link-clicks.track_tag_mentions', false);

        $post = $this->insertPost('<t><p><TAGMENTION id="1" slug="support" tagname="Support">#support</TAGMENTION></p></t>');
        $this->fire(new Posted($post));

        $this->assertSame(0, PostLink::query()->where('source', PostLink::SOURCE_TAG_MENTION)->count());
    }

    private function insertPost(string $parsedXml): CommentPost
    {
        $this->database()->table('posts')->insert([
            'id' => 1,
            'discussion_id' => 1,
            'number' => 1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => $parsedXml,
            'is_private' => 0,
        ]);

        return CommentPost::query()->findOrFail(1);
    }

    private function fire(object $event): void
    {
        $this->app()->getContainer()->make(Dispatcher::class)->dispatch($event);
    }
}
