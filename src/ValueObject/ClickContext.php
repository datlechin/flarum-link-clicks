<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\ValueObject;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\User\User;

final readonly class ClickContext
{
    public function __construct(
        public User $actor,
        public string $ipAddress,
        public string $userAgentRaw,
        public bool $dnt,
        public PostLink $postLink,
        public Carbon $now,
    ) {
    }
}
