<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Contract;

use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;

/**
 * One kind of clickable thing inside a post.
 *
 * A post's rendered HTML contains several kinds of link, each produced by a
 * different s9e/TextFormatter tag: plain `<URL>` links, `#hashtag` tag
 * mentions, `@username` user mentions, and post mentions. They all behave the
 * same way once a click has been recorded, so everything downstream of
 * `post_links` (the recorder, the click policy, GDPR, reconcile, the daily
 * rollup) is deliberately source-agnostic. What differs is only how a target
 * is found in the parsed XML, how tracking attributes are attached at render
 * time, and where a click should ultimately land.
 *
 * Implementations are registered through the public
 * {@see \Datlechin\LinkClicks\Extend\TrackableSources} extender, so other
 * extensions can add their own without touching this package.
 */
interface TrackableSource
{
    /**
     * Discriminator persisted verbatim in `post_links.source`.
     *
     * This value is written to disk and cannot change once released. Keep it
     * snake_case, and namespace it (`acme-polls.vote`) when the source ships
     * in a third-party extension.
     */
    public function key(): string;

    /**
     * The s9e/TextFormatter tag this source reads and rewrites, e.g. `URL`
     * or `TAGMENTION`.
     */
    public function tagName(): string;

    /**
     * Every trackable target in a post's stored parsed XML.
     *
     * Only parse-time attributes are readable here: `Post::$parsed_content` is
     * a frozen column, so anything a render pass resolves later (mentions'
     * `slug`, `deleted`, display names) is not guaranteed to be present.
     * Resolve whatever live state is needed from the database instead, and
     * silently drop occurrences whose target no longer exists.
     *
     * Return every target found, including ones a setting says not to persist:
     * {@see shouldPersist()} decides that, and the syncer needs the full set
     * so that flipping a setting off never orphans rows that are still in the
     * post.
     *
     * @return array<string, TrackedTarget> keyed by TrackedTarget::$hash
     */
    public function extract(string $parsedXml): array;

    /**
     * Whether a target found by {@see extract()} should get a `post_links`
     * row. Lets a source honour its own settings without the syncer knowing
     * about them.
     */
    public function shouldPersist(TrackedTarget $target): bool;

    /**
     * Attribute names this source's {@see apply()} may set, so the XSL
     * template can be patched to forward them into the rendered HTML.
     *
     * Never list an attribute the tag's own template already computes, the
     * mention templates build `class` and `style` with `<xsl:attribute>`, and
     * forwarding those would collide with them.
     *
     * @return list<string>
     */
    public function forwardedAttributes(): array;

    /**
     * The identity hash for one rendered occurrence, matching the hash
     * {@see extract()} produced for the same target, or null to leave the
     * occurrence untouched.
     *
     * @param array<string, string> $attrs
     */
    public function identify(array $attrs): ?string;

    /**
     * Attach tracking attributes to one occurrence.
     *
     * @param  array<string, string> $attrs
     * @return array<string, string>
     */
    public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array;

    /**
     * Where a click on this link should actually land.
     *
     * Resolved at click time rather than read from `post_links.url`, because a
     * mentioned tag or user can be renamed after the post was written and the
     * stored URL would then point at a slug that no longer exists. Returning
     * null aborts the redirect with a 404.
     */
    public function resolveTarget(PostLink $link): ?string;
}
