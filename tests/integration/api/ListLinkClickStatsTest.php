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
use Psr\Http\Message\ServerRequestInterface;

class ListLinkClickStatsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

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
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://github.com/repo', 'url_hash' => hash('sha256', 'https://github.com/repo'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
                ['id' => 2, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/x', 'url_hash' => hash('sha256', 'https://example.com/x'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
                ['id' => 3, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://internal.test/y', 'url_hash' => hash('sha256', 'https://internal.test/y'), 'is_internal' => 1, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
            ],
            'link_click_events' => [
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(2)],
                ['post_link_id' => 1, 'user_id' => 4, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(1)],
                ['post_link_id' => 1, 'user_id' => 4, 'counted' => 1, 'clicked_at' => Carbon::now()->subHours(2)],
                ['post_link_id' => 2, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(3)],
                ['post_link_id' => 3, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDays(1)],
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 0, 'clicked_at' => Carbon::now()->subDays(40)],
            ],
        ]);
    }

    #[Test]
    public function aggregates_by_url_with_total_and_unique_users(): void
    {
        $body = $this->callStats(authenticatedAs: 1);

        $this->assertSame(3, $body['total']);
        $first = $body['rows'][0];
        $this->assertSame('https://github.com/repo', $first['url']);
        $this->assertSame(3, $first['total_clicks']);
        $this->assertSame(2, $first['unique_users']);
    }

    #[Test]
    public function only_counts_counted_events(): void
    {
        $body = $this->callStats(authenticatedAs: 1);

        $github = collect($body['rows'])->firstWhere('url', 'https://github.com/repo');
        $this->assertSame(3, $github['total_clicks']);
    }

    #[Test]
    public function date_range_filter_excludes_old_clicks(): void
    {
        $since = Carbon::now()->subDay()->format('Y-m-d');
        $until = Carbon::now()->format('Y-m-d');

        $body = $this->callStats(
            authenticatedAs: 1,
            queryString: "filter[since]={$since}&filter[until]={$until}",
        );

        $this->assertCount(2, $body['rows']);
    }

    #[Test]
    public function scope_filter_returns_external_only_when_set(): void
    {
        $body = $this->callStats(
            authenticatedAs: 1,
            queryString: 'filter[is_internal]=0',
        );

        foreach ($body['rows'] as $row) {
            $this->assertFalse($row['is_internal']);
        }
    }

    #[Test]
    public function returns_403_for_non_admin(): void
    {
        $request = $this->request('GET', '/api/link-click-stats', ['authenticatedAs' => 2]);
        $response = $this->send($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function returns_401_for_guest(): void
    {
        $response = $this->send($this->request('GET', '/api/link-click-stats'));

        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, offset: int, limit: int}
     */
    private function callStats(int $authenticatedAs, string $queryString = ''): array
    {
        $url = '/api/link-click-stats'.($queryString ? '?'.$queryString : '');
        $request = $this->withQueryParamsFromUrl($this->request('GET', $url, ['authenticatedAs' => $authenticatedAs]), $url);

        $response = $this->send($request);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }

    private function withQueryParamsFromUrl(ServerRequestInterface $request, string $url): ServerRequestInterface
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return $request;
        }
        parse_str($query, $params);

        return $request->withQueryParams($params);
    }
}
