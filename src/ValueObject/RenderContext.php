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

use Flarum\Post\CommentPost;

/**
 * What a source needs to know about the post being rendered, so that
 * {@see \Datlechin\LinkClicks\Contract\TrackableSource::apply()} doesn't have
 * to re-read settings once per link.
 */
final readonly class RenderContext
{
    public function __construct(
        public CommentPost $post,
        public int $minDisplayCount,
    ) {
    }
}
