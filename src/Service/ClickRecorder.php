<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Service;

use Datlechin\LinkClicks\Event\ClickCounted;
use Datlechin\LinkClicks\Event\ClickRecorded;
use Datlechin\LinkClicks\LinkClickEvent;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\ClickContext;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

/**
 * Owns the side effects of a click: writing the event row, atomically bumping
 * the counter, and dispatching domain events.
 *
 * Race-safety: the dedup probe and counter increment both run inside one
 * transaction that takes a row lock on the `post_links` row first. This
 * serialises concurrent clicks for the same link; parallelism across
 * different links is preserved.
 */
class ClickRecorder
{
    public function __construct(
        protected ClickPolicy $policy,
        protected ConnectionInterface $db,
        protected Dispatcher $events,
        protected UserAgentClassifier $ua,
    ) {
    }

    /**
     * Returns true if an event row was written, false if the request was
     * dropped at the bot/DNT gate.
     */
    public function record(ClickContext $ctx): bool
    {
        if (! $this->policy->shouldRecord($ctx)) {
            return false;
        }

        $newCount = null;

        $counted = $this->db->transaction(function () use ($ctx, &$newCount): bool {
            // Lock the post_links row so the dedup probe and counter
            // increment are serialised against any concurrent click for
            // the same link.
            /** @var PostLink|null $locked */
            $locked = PostLink::query()
                ->where('id', $ctx->postLink->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return false;
            }

            $shouldCount = $this->policy->isCounted($ctx);

            $isGuest = $ctx->actor->isGuest();

            $hasUa = $ctx->userAgentRaw !== '';

            LinkClickEvent::query()->create([
                'post_link_id' => $ctx->postLink->id,
                'user_id' => $isGuest ? null : $ctx->actor->id,
                'ip_address' => $ctx->ipAddress !== '' ? $ctx->ipAddress : null,
                'user_agent' => $hasUa ? $ctx->userAgentRaw : null,
                'device_class' => $hasUa ? $this->ua->classifyDevice($ctx->userAgentRaw) : null,
                'browser_family' => $hasUa ? $this->ua->classifyBrowser($ctx->userAgentRaw) : null,
                'counted' => $shouldCount,
                'clicked_at' => $ctx->now,
            ]);

            if ($shouldCount) {
                $newCount = $locked->clicks_count + 1;
                PostLink::query()
                    ->where('id', $ctx->postLink->id)
                    ->update([
                        'clicks_count' => $newCount,
                        'last_clicked_at' => $ctx->now,
                    ]);
            }

            return $shouldCount;
        });

        $this->events->dispatch(new ClickRecorded($ctx, $counted));

        if ($counted && $newCount !== null) {
            // Compose the broadcast payload from the value we just committed.
            // A fresh re-read could be inflated by a concurrent click that
            // committed between our commit and the re-read.
            $ctx->postLink->clicks_count = $newCount;
            $this->events->dispatch(new ClickCounted($ctx->postLink));
        }

        return true;
    }
}
