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

use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use s9e\SweetDOM\Element;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

/**
 * Patches the s9e XSL template of every registered source's tag so that the
 * attributes we inject at render time pass through to the rendered HTML.
 *
 * Without this, setting `$attrs['data-clicks']` in the render callback is
 * silently dropped, because the XSL renderer only emits attributes that the
 * template explicitly copies. We pre-declare every attribute a source will
 * ever set so the cache only needs to be rebuilt once.
 *
 * A source whose tag isn't registered is skipped: `TAGMENTION` only exists
 * when flarum/mentions and flarum/tags are both enabled, and the tag is
 * registered inside another extension's own configure callback. Our callback
 * therefore has to run after theirs, which is what the `optional-dependencies`
 * entry in composer.json guarantees, Flarum topologically sorts extension
 * boot order over that graph, and formatter callbacks run in boot order.
 *
 * Runs once when the formatter cache is built (and again when the extension
 * is enabled / disabled, which flushes the cache).
 */
class ConfigureTrackableTemplates
{
    public function __construct(
        protected TrackableSourceRegistry $sources,
    ) {
    }

    public function __invoke(Configurator $configurator): void
    {
        foreach ($this->sources->all() as $source) {
            $tagName = $source->tagName();

            if (! isset($configurator->tags[$tagName])) {
                continue;
            }

            $template = $configurator->tags[$tagName]->template->asDOM();

            /** @var Element $a */
            foreach ($template->getElementsByTagName('a') as $a) {
                foreach ($source->forwardedAttributes() as $attr) {
                    $a->prependXslCopyOf('@'.$attr);
                }

                $this->makeHrefTrackable($a);
            }

            $template->saveChanges();
        }
    }

    /**
     * Let the render pass redirect a link by setting `data-lc-href`, without
     * touching the `url` attribute the link was written with.
     *
     * Rewriting `url` itself would be simpler, but core inspects that same
     * attribute after us to decide whether a link points back at the forum
     * (`Formatter::configureDefaultsOnLinks()`). Pointing it at our tracking
     * route makes every link look internal: external links lose their
     * `rel="ugc nofollow"`, and `routeInternalLinks` then intercepts the click
     * and hands `/lcc/track?u=...` to the SPA router, which has no such route.
     *
     * So the destination stays honest in the XML and only the rendered `href`
     * is swapped, falling back to the template's original expression whenever
     * the render pass didn't set a tracking URL, a disabled extension, an
     * opted-out post, or a target that isn't tracked.
     */
    private function makeHrefTrackable(Element $a): void
    {
        $original = $a->getAttribute('href');

        if ($original === '') {
            return;
        }

        $a->removeAttribute('href');

        $href = $a->prependXslAttribute('href');
        $choose = $href->appendXslChoose();

        $choose->appendXslWhen('@data-lc-href')->appendXslValueOf('@data-lc-href');

        $otherwise = $choose->appendXslOtherwise();

        // The original value is an attribute value template, where `{...}` is
        // an XPath expression. Inside xsl:attribute that syntax is just text,
        // so rebuild it as the equivalent nodes instead.
        foreach (AVTHelper::parse($original) as [$type, $content]) {
            if ($type === 'expression') {
                $otherwise->appendXslValueOf($content);
            } else {
                $otherwise->appendXslText($content);
            }
        }
    }
}
