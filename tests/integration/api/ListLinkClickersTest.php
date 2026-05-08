<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListLinkClickersTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const URL = 'https://example.com/popular';
    private string $hash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hash = hash('sha256', self::URL);
        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 4, 'username' => 'OtherUser', 'email' => 'o@l.com', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 't', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'],
            ],
            'post_links' => [
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => self::URL, 'url_hash' => $this->hash, 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
            ],
            'link_click_events' => [
                ['post_link_id' => 1, 'user_id' => 2, 'ip_address' => '1.1.1.1', 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(2)],
                ['post_link_id' => 1, 'user_id' => 2, 'ip_address' => '1.1.1.1', 'counted' => 1, 'clicked_at' => Carbon::now()->subDay()],
                ['post_link_id' => 1, 'user_id' => 4, 'ip_address' => '2.2.2.2', 'counted' => 1, 'clicked_at' => Carbon::now()->subHours(3)],
                ['post_link_id' => 1, 'user_id' => null, 'ip_address' => '9.9.9.9', 'counted' => 1, 'clicked_at' => Carbon::now()->subHour()],
                // anonymized (GDPR-erased) row should collapse to one bucket
                ['post_link_id' => 1, 'user_id' => null, 'ip_address' => null, 'counted' => 1, 'clicked_at' => Carbon::now()->subMinutes(30)],
                ['post_link_id' => 1, 'user_id' => null, 'ip_address' => null, 'counted' => 1, 'clicked_at' => Carbon::now()->subMinutes(15)],
                // uncounted row must not show up
                ['post_link_id' => 1, 'user_id' => 2, 'ip_address' => '1.1.1.1', 'counted' => 0, 'clicked_at' => Carbon::now()->subDays(40)],
            ],
        ]);
    }

    #[Test]
    public function admin_gets_grouped_clickers_with_counts(): void
    {
        $body = $this->call(authenticatedAs: 1);

        // Groups: user 2 (2 clicks), user 4 (1), guest 9.9.9.9 (1), anonymized bucket (2 clicks merged)
        $this->assertSame(4, $body['total']);
        $this->assertCount(4, $body['rows']);

        $userTwo = collect($body['rows'])->firstWhere(fn ($r) => $r['user'] && $r['user']['id'] === 2);
        $this->assertNotNull($userTwo);
        $this->assertSame(2, $userTwo['click_count']);
    }

    #[Test]
    public function anonymized_rows_collapse_into_one_bucket(): void
    {
        $body = $this->call(authenticatedAs: 1);

        $anonRows = array_values(array_filter($body['rows'], fn ($r) => $r['anonymized'] === true));
        $this->assertCount(1, $anonRows);
        $this->assertSame(2, $anonRows[0]['click_count']);
    }

    #[Test]
    public function guest_ip_is_surfaced_for_anonymous_clicks(): void
    {
        $body = $this->call(authenticatedAs: 1);

        $guestRows = array_values(array_filter($body['rows'], fn ($r) => $r['user'] === null && $r['ip_address'] === '9.9.9.9'));
        $this->assertCount(1, $guestRows);
        $this->assertFalse($guestRows[0]['anonymized']);
    }

    #[Test]
    public function uncounted_clicks_are_excluded(): void
    {
        $body = $this->call(authenticatedAs: 1);

        $userTwo = collect($body['rows'])->firstWhere(fn ($r) => $r['user'] && $r['user']['id'] === 2);
        $this->assertSame(2, $userTwo['click_count']);
    }

    #[Test]
    public function bad_hash_returns_422(): void
    {
        $response = $this->send($this->request('GET', '/api/link-click-stats/notahex/clickers', ['authenticatedAs' => 1]));
        $this->assertSame(422, $response->getStatusCode());
    }

    #[Test]
    public function non_admin_rejected(): void
    {
        $response = $this->send($this->request('GET', "/api/link-click-stats/{$this->hash}/clickers", ['authenticatedAs' => 2]));
        $this->assertSame(403, $response->getStatusCode());
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, offset: int, limit: int}
     */
    private function call(int $authenticatedAs): array
    {
        $response = $this->send($this->request('GET', "/api/link-click-stats/{$this->hash}/clickers", ['authenticatedAs' => $authenticatedAs]));
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }
}
