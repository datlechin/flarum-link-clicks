<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Http;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class PopularLinksTest extends TestCase
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
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://github.com/user/repo', 'url_hash' => hash('sha256', 'https://github.com/user/repo'), 'is_internal' => 0, 'clicks_count' => 10, 'first_seen_at' => Carbon::now()],
                ['id' => 2, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/page1', 'url_hash' => hash('sha256', 'https://example.com/page1'), 'is_internal' => 0, 'clicks_count' => 5, 'first_seen_at' => Carbon::now()],
                ['id' => 3, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/page2', 'url_hash' => hash('sha256', 'https://example.com/page2'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
                ['id' => 4, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://internal.test/x', 'url_hash' => hash('sha256', 'https://internal.test/x'), 'is_internal' => 1, 'clicks_count' => 100, 'first_seen_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function returns_top_links_sorted_by_count_desc(): void
    {
        $data = $this->callPopular(1);

        $this->assertCount(2, $data);
        $this->assertSame(1, $data[0]['id']);
        $this->assertSame(10, $data[0]['count']);
        $this->assertSame(2, $data[1]['id']);
        $this->assertSame(5, $data[1]['count']);
    }

    #[Test]
    public function excludes_internal_links(): void
    {
        $ids = array_column($this->callPopular(1), 'id');
        $this->assertNotContains(4, $ids);
    }

    #[Test]
    public function excludes_links_with_zero_clicks(): void
    {
        $ids = array_column($this->callPopular(1), 'id');
        $this->assertNotContains(3, $ids);
    }

    #[Test]
    public function respects_min_display_count_setting(): void
    {
        $this->setting('datlechin-link-clicks.min_display_count', 7);

        $data = $this->callPopular(1);

        $this->assertCount(1, $data);
        $this->assertSame(1, $data[0]['id']);
    }

    #[Test]
    public function payload_carries_signed_track_url_and_full_title(): void
    {
        $first = $this->callPopular(1)[0];

        $this->assertArrayHasKey('track_url', $first);
        $this->assertMatchesRegularExpression('#/lcc/track\?u=[A-Za-z0-9_-]+#', $first['track_url']);
        $this->assertSame('https://github.com/user/repo', $first['title']);
        $this->assertSame('github.com/user/repo', $first['display']);
    }

    #[Test]
    public function returns_404_for_missing_discussion(): void
    {
        $response = $this->send($this->request('GET', '/api/discussions/9999/popular-links'));
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function callPopular(int $discussionId): array
    {
        $response = $this->send($this->request('GET', "/api/discussions/{$discussionId}/popular-links"));
        $this->assertSame(200, $response->getStatusCode());

        return json_decode((string) $response->getBody(), true);
    }
}
