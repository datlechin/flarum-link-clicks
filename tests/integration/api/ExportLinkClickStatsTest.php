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

class ExportLinkClickStatsTest extends TestCase
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
                ['id' => 1, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/a', 'url_hash' => hash('sha256', 'https://example.com/a'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
                ['id' => 2, 'post_id' => 1, 'discussion_id' => 1, 'url' => 'https://example.com/b', 'url_hash' => hash('sha256', 'https://example.com/b'), 'is_internal' => 0, 'clicks_count' => 0, 'first_seen_at' => Carbon::now()],
            ],
            'link_click_events' => [
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDay()],
                ['post_link_id' => 1, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subHours(2)],
                ['post_link_id' => 2, 'user_id' => 2, 'counted' => 1, 'clicked_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    #[Test]
    public function admin_gets_csv_with_bom_and_correct_rows(): void
    {
        $response = $this->send($this->request('GET', '/api/link-click-stats/export', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/csv', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));

        $body = (string) $response->getBody();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV must start with UTF-8 BOM');

        $lines = array_values(array_filter(explode("\n", trim($body))));
        $this->assertCount(3, $lines, 'header + 2 data rows');
        $this->assertStringContainsString('url,is_internal,total_clicks', $lines[0]);
        $this->assertStringContainsString('https://example.com/a', $body);
        $this->assertStringContainsString('https://example.com/b', $body);
    }

    #[Test]
    public function non_admin_is_rejected(): void
    {
        $response = $this->send($this->request('GET', '/api/link-click-stats/export', ['authenticatedAs' => 2]));
        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function guest_is_rejected(): void
    {
        $response = $this->send($this->request('GET', '/api/link-click-stats/export'));
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }
}
