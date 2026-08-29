<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Provider;

use Datlechin\LinkClicks\Service\HmacKeyProvider;
use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use Flarum\Foundation\AbstractServiceProvider;

class LinkClicksProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(HmacKeyProvider::class);

        // Seeded empty so the TrackableSources extender has something to
        // decorate, whatever order the extenders run in.
        $this->container->singleton(TrackableSourceRegistry::class);
    }
}
