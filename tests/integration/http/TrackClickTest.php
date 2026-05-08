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
use Datlechin\LinkClicks\LinkClickEvent;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TrackClickTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'OtherUser', 'email' => 'other@test.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'],
            ],
            'post_links' => [
                [
                    'id' => 1,
                    'post_id' => 1,
                    'discussion_id' => 1,
                    'url' => 'https://example.com/page',
                    'url_hash' => hash('sha256', 'https://example.com/page'),
                    'is_internal' => 0,
                    'clicks_count' => 0,
                    'first_seen_at' => Carbon::now(),
                ],
            ],
        ]);
    }

    #[Test]
    public function missing_token_returns_404(): void
    {
        $this->assertSame(404, $this->send($this->trackRequest(null))->getStatusCode());
    }

    #[Test]
    public function invalid_token_returns_404(): void
    {
        $this->assertSame(404, $this->send($this->trackRequest('bogus'))->getStatusCode());
    }

    #[Test]
    public function token_for_missing_post_link_returns_404(): void
    {
        $this->assertSame(404, $this->send($this->trackRequest($this->signer()->sign(99999)))->getStatusCode());
    }

    #[Test]
    public function valid_click_redirects_and_records_counted_event(): void
    {
        // user 3 is not the author (post.user_id = 2)
        $response = $this->send($this->trackRequest($this->signer()->sign(1), 3));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/page', $response->getHeaderLine('Location'));

        $event = LinkClickEvent::query()->where('post_link_id', 1)->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->counted);
        $this->assertSame(3, $event->user_id);

        $this->assertSame(1, PostLink::query()->find(1)->clicks_count);
    }

    #[Test]
    public function author_click_is_recorded_but_not_counted(): void
    {
        $response = $this->send($this->trackRequest($this->signer()->sign(1), 2));

        $this->assertSame(302, $response->getStatusCode());

        $event = LinkClickEvent::query()->where('post_link_id', 1)->first();
        $this->assertNotNull($event);
        $this->assertFalse($event->counted);
        $this->assertSame(0, PostLink::query()->find(1)->clicks_count);
    }

    #[Test]
    public function duplicate_click_within_window_is_not_counted(): void
    {
        $this->send($this->trackRequest($this->signer()->sign(1), 3));
        $this->assertSame(1, PostLink::query()->find(1)->clicks_count);

        $this->send($this->trackRequest($this->signer()->sign(1), 3));
        $this->assertSame(1, PostLink::query()->find(1)->clicks_count);

        $this->assertSame(2, LinkClickEvent::query()->where('post_link_id', 1)->count());
        $this->assertSame(1, LinkClickEvent::query()->where('post_link_id', 1)->where('counted', true)->count());
    }

    #[Test]
    public function guest_click_is_recorded_with_ip_address(): void
    {
        // ProcessIp middleware overrides the request attribute from REMOTE_ADDR,
        // so we can't inject a custom IP from the test. Just assert it's set.
        $response = $this->send($this->trackRequest($this->signer()->sign(1)));

        $this->assertSame(302, $response->getStatusCode());

        $event = LinkClickEvent::query()->where('post_link_id', 1)->first();
        $this->assertNotNull($event);
        $this->assertNull($event->user_id);
        $this->assertNotEmpty($event->ip_address);
        $this->assertTrue($event->counted);
    }

    #[Test]
    public function dnt_request_is_not_recorded_at_all(): void
    {
        $request = $this->trackRequest($this->signer()->sign(1), 3)->withHeader('DNT', '1');

        $this->assertSame(302, $this->send($request)->getStatusCode());
        $this->assertSame(0, LinkClickEvent::query()->where('post_link_id', 1)->count());
        $this->assertSame(0, PostLink::query()->find(1)->clicks_count);
    }

    #[Test]
    public function bot_user_agent_is_not_recorded(): void
    {
        $request = $this->trackRequest($this->signer()->sign(1), 3)
            ->withHeader('User-Agent', 'Googlebot/2.1');

        $this->assertSame(302, $this->send($request)->getStatusCode());
        $this->assertSame(0, LinkClickEvent::query()->where('post_link_id', 1)->count());
    }

    #[Test]
    public function user_agent_is_stored_when_present(): void
    {
        $ua = 'Mozilla/5.0 (Macintosh) Chrome/120.0';
        $request = $this->trackRequest($this->signer()->sign(1), 3)
            ->withHeader('User-Agent', $ua);

        $this->send($request);

        $event = LinkClickEvent::query()->where('post_link_id', 1)->first();
        $this->assertSame($ua, $event->user_agent);
    }

    private function trackRequest(?string $token, ?int $authenticatedAs = null): ServerRequestInterface
    {
        $opts = $authenticatedAs !== null ? ['authenticatedAs' => $authenticatedAs] : [];

        $request = $this->request('GET', '/lcc/track', $opts);

        return $token === null ? $request : $request->withQueryParams(['u' => $token]);
    }

    private function signer(): TrackingUrlSigner
    {
        return $this->app()->getContainer()->make(TrackingUrlSigner::class);
    }
}
