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

/**
 * One trackable thing found in a post, ready to become a `post_links` row.
 *
 * The `hash` is the row's identity within a post. For plain links it is a hash
 * of the canonical URL, so the same URL written twice collapses into one row.
 * For mentions it is a hash of the mentioned entity's id rather than its URL,
 * because a tag or user can be renamed and a URL-derived hash would then
 * fragment the click history across the rename.
 */
final readonly class TrackedTarget
{
    public function __construct(
        public string $hash,
        public string $url,
        public ?string $label = null,
        public bool $isInternal = false,
        public bool $isAttachment = false,
        public ?string $domain = null,
        public ?int $sourceId = null,
    ) {
    }
}
