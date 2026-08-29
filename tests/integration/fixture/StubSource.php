<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Fixture;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;

/**
 * A second source, so tests can prove behaviour that only shows up once more
 * than one kind of trackable thing exists. Always finds exactly one target,
 * independent of the post's content.
 */
class StubSource implements TrackableSource
{
    public const KEY = 'stub';

    public function key(): string
    {
        return self::KEY;
    }

    public function tagName(): string
    {
        return 'STUB';
    }

    public function extract(string $parsedXml): array
    {
        $hash = hash('sha256', self::KEY.':1');

        return [
            $hash => new TrackedTarget(
                hash: $hash,
                url: 'https://stub.test/target',
                label: 'stub target',
                isInternal: false,
            ),
        ];
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        return true;
    }

    public function forwardedAttributes(): array
    {
        return ['data-clicks'];
    }

    public function identify(array $attrs): ?string
    {
        return hash('sha256', self::KEY.':1');
    }

    public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
    {
        $attrs['data-lc-href'] = $trackingUrl;

        return $attrs;
    }

    public function resolveTarget(PostLink $link): ?string
    {
        return $link->url;
    }
}
