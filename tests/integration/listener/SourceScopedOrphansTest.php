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
use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\Extend\TrackableSources;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * Orphan deletion has to be scoped to the source that produced the rows.
 *
 * Deleting by post id alone would mean any source that didn't run, because
 * the extension providing it is currently disabled, say, silently loses every
 * row it ever wrote the next time someone edits the post.
 */
class SourceScopedOrphansTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->extend(
            (new TrackableSources())->add(StubSource::class),
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
        ]);
    }

    #[Test]
    public function each_source_gets_its_own_row_for_the_same_post(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/a">a</URL></p></t>');

        $this->fire(new Posted($post));

        $this->assertEqualsCanonicalizing(
            [PostLink::SOURCE_URL, StubSource::KEY],
            PostLink::query()->where('post_id', 1)->pluck('source')->all(),
        );
    }

    #[Test]
    public function editing_out_a_url_leaves_another_sources_rows_alone(): void
    {
        $post = $this->insertPost(1, '<t><p><URL url="https://example.com/a">a</URL></p></t>');
        $this->fire(new Posted($post));

        $this->updatePostContent(1, '<t><p>No links any more.</p></t>');
        $this->fire(new Revised(CommentPost::query()->findOrFail(1), $this->actor(), ''));

        $this->assertSame(
            0,
            PostLink::query()->where('post_id', 1)->where('source', PostLink::SOURCE_URL)->count(),
            'the URL that was edited out should be gone',
        );
        $this->assertSame(
            1,
            PostLink::query()->where('post_id', 1)->where('source', StubSource::KEY)->count(),
            'a source that still finds its target should keep its row',
        );
    }

    private function insertPost(int $id, string $parsedXml): CommentPost
    {
        $this->database()->table('posts')->insert([
            'id' => $id,
            'discussion_id' => 1,
            'number' => 1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'user_id' => 2,
            'type' => 'comment',
            'content' => $parsedXml,
            'is_private' => 0,
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
}

/**
 * A second source, so the test can prove behaviour that only appears once more
 * than one kind of trackable thing exists. Always finds exactly one target,
 * whatever the post says.
 *
 * Declared here rather than in its own file because the container resolves it
 * by name: a separate file would have to sit at a PSR-4 path matching its
 * namespace exactly, which is easy to get wrong on a case-insensitive
 * filesystem and only fails on Linux.
 */
class StubSource implements TrackableSource
{
    public const KEY = 'stub';

    public function key(): string
    {
        return self::KEY;
    }

    public function tagName(): string
    {
        return 'STUB';
    }

    public function extract(string $parsedXml): array
    {
        $hash = hash('sha256', self::KEY.':1');

        return [
            $hash => new TrackedTarget(
                hash: $hash,
                url: 'https://stub.test/target',
                label: 'stub target',
            ),
        ];
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        return true;
    }

    public function forwardedAttributes(): array
    {
        return ['data-clicks'];
    }

    public function identify(array $attrs): ?string
    {
        return hash('sha256', self::KEY.':1');
    }

    public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
    {
        $attrs['data-lc-href'] = $trackingUrl;

        return $attrs;
    }

    public function resolveTarget(PostLink $link): ?string
    {
        return $link->url;
    }
}
