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

final readonly class NormalizedUrl
{
    public function __construct(
        public string $value,
        public string $hash,
        public string $host,
        public bool $isAttachment = false,
    ) {
    }
}
