<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Listener;

use Datlechin\LinkClicks\Event\ClickCounted;
use Datlechin\LinkClicks\Push\Jobs\SendCountedClickJob;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Log\LoggerInterface;

class BroadcastWhenClickCounted
{
    public function __construct(
        protected Dispatcher $bus,
        protected TranslatorInterface $translator,
        protected LoggerInterface $log,
    ) {
    }

    public function handle(ClickCounted $event): void
    {
        // Broadcast is best-effort. The click is already committed to the DB,
        // so a failure here must not surface as a 500 on the user's redirect.
        try {
            $postLink = $event->postLink;

            $title = $this->translator->trans(
                'datlechin-link-clicks.forum.link_tooltip',
                ['count' => $postLink->clicks_count],
            );

            $this->bus->dispatch(new SendCountedClickJob(
                postId: $postLink->post_id,
                urlId: $postLink->id,
                discussionId: $postLink->discussion_id,
                clicksCount: $postLink->clicks_count,
                title: (string) $title,
            ));
        } catch (\Throwable $e) {
            $this->log->error('[datlechin/flarum-link-clicks] failed to broadcast click', [
                'exception' => $e,
            ]);
        }
    }
}
