<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Extend;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Registers a new kind of clickable thing for Link Clicks to track.
 *
 * ```php
 * (new TrackableSources())
 *     ->add(MyPollVoteSource::class),
 * ```
 *
 * The source is picked up by extraction, rendering, click recording and every
 * analytics surface without further wiring. Register inside an
 * `Extend\Conditional()->whenExtensionEnabled(...)` block when the source
 * depends on another extension being present.
 */
class TrackableSources implements ExtenderInterface
{
    /** @var list<class-string<TrackableSource>> */
    private array $sources = [];

    /**
     * @param class-string<TrackableSource> $sourceClass
     */
    public function add(string $sourceClass): self
    {
        $this->sources[] = $sourceClass;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend(
            TrackableSourceRegistry::class,
            function (TrackableSourceRegistry $registry, Container $container) {
                foreach ($this->sources as $sourceClass) {
                    $registry->add($container->make($sourceClass));
                }

                return $registry;
            },
        );
    }
}
