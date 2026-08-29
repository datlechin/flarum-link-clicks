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

use Datlechin\LinkClicks\Contract\TrackableSource;

/**
 * Every registered {@see TrackableSource}, keyed by its discriminator.
 *
 * Populated by the {@see \Datlechin\LinkClicks\Extend\TrackableSources}
 * extender during container boot, so a source belonging to a disabled
 * extension is simply never added rather than needing a runtime liveness
 * check.
 */
class TrackableSourceRegistry
{
    /** @var array<string, TrackableSource> */
    private array $sources = [];

    public function add(TrackableSource $source): void
    {
        $this->sources[$source->key()] = $source;
    }

    /**
     * @return array<string, TrackableSource>
     */
    public function all(): array
    {
        return $this->sources;
    }

    /**
     * The source that owns a `post_links.source` value, or null when the
     * extension that registered it is no longer enabled.
     */
    public function find(string $key): ?TrackableSource
    {
        return $this->sources[$key] ?? null;
    }
}
