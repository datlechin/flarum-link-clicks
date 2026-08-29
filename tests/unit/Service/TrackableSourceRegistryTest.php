<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\unit\Service;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TrackableSourceRegistryTest extends TestCase
{
    #[Test]
    public function registered_sources_are_keyed_by_their_discriminator(): void
    {
        $registry = new TrackableSourceRegistry();
        $registry->add($this->source('url'));
        $registry->add($this->source('tag_mention'));

        $this->assertSame(['url', 'tag_mention'], array_keys($registry->all()));
    }

    #[Test]
    public function find_returns_the_source_owning_a_key(): void
    {
        $registry = new TrackableSourceRegistry();
        $registry->add($this->source('tag_mention'));

        $this->assertSame('tag_mention', $registry->find('tag_mention')?->key());
    }

    /**
     * A `post_links` row outlives the extension that registered its source, so
     * looking one up after that extension is disabled has to be answerable
     * rather than fatal. Callers turn the null into a 404.
     */
    #[Test]
    public function find_returns_null_for_a_source_that_is_no_longer_registered(): void
    {
        $registry = new TrackableSourceRegistry();
        $registry->add($this->source('url'));

        $this->assertNull($registry->find('tag_mention'));
    }

    private function source(string $key): TrackableSource
    {
        return new class($key) implements TrackableSource {
            public function __construct(private readonly string $key)
            {
            }

            public function key(): string
            {
                return $this->key;
            }

            public function tagName(): string
            {
                return 'STUB';
            }

            public function extract(string $parsedXml): array
            {
                return [];
            }

            public function shouldPersist(TrackedTarget $target): bool
            {
                return true;
            }

            public function forwardedAttributes(): array
            {
                return [];
            }

            public function identify(array $attrs): ?string
            {
                return null;
            }

            public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
            {
                return $attrs;
            }

            public function resolveTarget(PostLink $link): ?string
            {
                return $link->url;
            }
        };
    }
}
