<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Formatter;

use s9e\TextFormatter\Configurator;

/**
 * Patches the s9e XSL template for `<URL>` tags so that the attributes we
 * inject at render time pass through to the rendered HTML.
 *
 * Without this, setting `$attrs['data-clicks']` in the render callback is
 * silently dropped, because the XSL renderer only emits attributes that the template
 * explicitly copies. We pre-declare every attribute we will ever set so the
 * cache only needs to be rebuilt once across phases.
 *
 * Runs once when the formatter cache is built (and again when the extension
 * is enabled / disabled, which flushes the cache).
 */
class ConfigureUrlTemplate
{
    private const FORWARDED_ATTRS = [
        'data-clicks',
        'data-post-id',
        'data-url-id',
        'data-custom-title',
        'class',
        'target',
        'rel',
    ];

    public function __invoke(Configurator $configurator): void
    {
        if (! isset($configurator->tags['URL'])) {
            return;
        }

        $template = $configurator->tags['URL']->template->asDOM();

        /** @var \s9e\SweetDOM\Element $a */
        foreach ($template->getElementsByTagName('a') as $a) {
            foreach (self::FORWARDED_ATTRS as $attr) {
                $a->prependXslCopyOf('@'.$attr);
            }
        }

        $template->saveChanges();
    }
}
