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
use Datlechin\LinkClicks\Source\UserMentionSource;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * `@username` and post mentions. Both follow TagMentionSource's shape: keyed
 * on the id of the thing mentioned, so a rename or a moved post doesn't split
 * the click history.
 */
class MentionSourcesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions', 'datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 2],
            ],
        ]);
    }

    #[Test]
    public function a_user_mention_is_keyed_on_the_users_id(): void
    {
        $post = $this->insertPost(2, '<t><p><USERMENTION displayname="normal" id="2">@normal</USERMENTION></p></t>');

        $this->fire(new Posted($post));

        $link = PostLink::query()->where('source', PostLink::SOURCE_USER_MENTION)->firstOrFail();

        $this->assertSame(2, $link->source_id);
        $this->assertSame(hash('sha256', 'user_mention:2'), $link->url_hash);
        $this->assertStringStartsWith('@', (string) $link->label);
    }

    #[Test]
    public function renaming_a_user_keeps_the_same_row(): void
    {
        $post = $this->insertPost(2, '<t><p><USERMENTION displayname="normal" id="2">@normal</USERMENTION></p></t>');
        $this->fire(new Posted($post));

        $before = PostLink::query()->where('source', PostLink::SOURCE_USER_MENTION)->firstOrFail();

        $this->database()->table('users')->where('id', 2)->update(['username' => 'renamed']);
        $this->fire(new Posted(CommentPost::query()->findOrFail(2)));

        $rows = PostLink::query()->where('source', PostLink::SOURCE_USER_MENTION)->get();

        $this->assertCount(1, $rows);
        $this->assertSame($before->id, $rows->first()->id);
    }

    #[Test]
    public function a_post_mention_is_keyed_on_the_mentioned_posts_id(): void
    {
        $this->insertPost(1, '<t><p>the post being quoted</p></t>');
        $post = $this->insertPost(2, '<t><p><POSTMENTION discussionid="1" displayname="normal" id="1" number="1">@normal</POSTMENTION></p></t>');

        $this->fire(new Posted($post));

        $link = PostLink::query()->where('source', PostLink::SOURCE_POST_MENTION)->firstOrFail();

        $this->assertSame(1, $link->source_id);
        $this->assertSame(hash('sha256', 'post_mention:1'), $link->url_hash);
    }

    /**
     * Asked of the source directly rather than through a Posted event: the
     * mentions extension listens for that too and would try to record a
     * mention of a user that isn't there, tripping its own foreign key before
     * this assertion is ever reached.
     */
    #[Test]
    public function a_mention_of_something_that_no_longer_exists_is_skipped(): void
    {
        $source = $this->app()->getContainer()->make(UserMentionSource::class);

        $targets = $source->extract('<t><p><USERMENTION displayname="ghost" id="9999">@ghost</USERMENTION></p></t>');

        $this->assertSame([], $targets);
    }

    #[Test]
    public function settings_turn_each_mention_kind_off_independently(): void
    {
        $this->setting('datlechin-link-clicks.track_user_mentions', false);

        $this->insertPost(1, '<t><p>quoted</p></t>');
        $post = $this->insertPost(2, '<t><p><USERMENTION displayname="normal" id="2">@normal</USERMENTION> <POSTMENTION discussionid="1" displayname="normal" id="1" number="1">@normal</POSTMENTION></p></t>');

        $this->fire(new Posted($post));

        $this->assertSame(0, PostLink::query()->where('source', PostLink::SOURCE_USER_MENTION)->count());
        $this->assertSame(1, PostLink::query()->where('source', PostLink::SOURCE_POST_MENTION)->count());
    }

    private function insertPost(int $id, string $parsedXml): CommentPost
    {
        $this->database()->table('posts')->insert([
            'id' => $id,
            'discussion_id' => 1,
            'number' => $id,
            'created_at' => Carbon::now()->toDateTimeString(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => $parsedXml,
            'is_private' => 0,
        ]);

        return CommentPost::query()->findOrFail($id);
    }

    private function fire(object $event): void
    {
        $this->app()->getContainer()->make(Dispatcher::class)->dispatch($event);
    }
}
