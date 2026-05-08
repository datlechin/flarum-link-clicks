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
use Datlechin\LinkClicks\Event\ClickCounted;
use Datlechin\LinkClicks\Listener\BroadcastWhenClickCounted;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Push\Jobs\SendCountedClickJob;
use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BroadcastWhenClickCountedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime', 'datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 't', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
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
                    'clicks_count' => 5,
                    'first_seen_at' => Carbon::now(),
                ],
            ],
        ]);
    }

    #[Test]
    public function listener_dispatches_send_job_with_payload_from_post_link(): void
    {
        $this->app();

        $captured = null;
        $bus = $this->createMock(BusDispatcher::class);
        $bus->method('dispatch')->willReturnCallback(function ($job) use (&$captured) {
            $captured = $job;

            return $job;
        });

        $this->makeListener($bus)->handle(new ClickCounted(PostLink::query()->findOrFail(1)));

        $this->assertInstanceOf(SendCountedClickJob::class, $captured);
        $this->assertSame(1, $captured->postId);
        $this->assertSame(1, $captured->urlId);
        $this->assertSame(1, $captured->discussionId);
        $this->assertSame(5, $captured->clicksCount);
        $this->assertNotEmpty($captured->title);
    }

    #[Test]
    public function listener_dispatches_exactly_once_per_event(): void
    {
        $this->app();

        $bus = $this->createMock(BusDispatcher::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SendCountedClickJob::class));

        $this->makeListener($bus)->handle(new ClickCounted(PostLink::query()->findOrFail(1)));
    }

    #[Test]
    public function listener_swallows_translator_exceptions_and_logs(): void
    {
        $this->app();

        $bus = $this->createMock(BusDispatcher::class);
        $bus->expects($this->never())->method('dispatch');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willThrowException(new \RuntimeException('boom'));

        $entries = [];
        $log = new class($entries) extends NullLogger {
            public function __construct(public array &$entries)
            {
            }

            public function error(string|\Stringable $message, array $context = []): void
            {
                $this->entries[] = (string) $message;
            }
        };

        $listener = new BroadcastWhenClickCounted($bus, $translator, $log);
        $listener->handle(new ClickCounted(PostLink::query()->findOrFail(1)));

        $this->assertNotEmpty($entries);
    }

    private function makeListener(BusDispatcher $bus): BroadcastWhenClickCounted
    {
        return new BroadcastWhenClickCounted(
            $bus,
            $this->app()->getContainer()->make(TranslatorInterface::class),
            $this->app()->getContainer()->make(LoggerInterface::class),
        );
    }
}
