<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Push\Jobs;

use Flarum\Discussion\Discussion;
use Flarum\Realtime\Push\Jobs\Job;

/**
 * Pushes a `linkClickCounted` Pusher event to every connected user that can
 * see the discussion, plus the public channel when the discussion is visible
 * to guests. The payload is intentionally tiny so it slots inside the 10 KB
 * Pusher limit without serialising a full JSON:API resource.
 */
class SendCountedClickJob extends Job
{
    public function __construct(
        public readonly int $postId,
        public readonly int $urlId,
        public readonly int $discussionId,
        public readonly int $clicksCount,
        public readonly string $title,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $discussion = Discussion::query()->find($this->discussionId);
        if ($discussion === null) {
            return;
        }

        $payload = [
            'post_id' => $this->postId,
            'url_id' => $this->urlId,
            'clicks_count' => $this->clicksCount,
            'title' => $this->title,
        ];

        $pusher = $this->pusher();

        foreach ($this->connectedUsers($discussion) as $user) {
            $pusher->trigger("private-user={$user->id}", 'linkClickCounted', $payload);
        }

        if ($this->visibleTo($discussion)) {
            $pusher->trigger('public', 'linkClickCounted', $payload);
        }
    }
}
