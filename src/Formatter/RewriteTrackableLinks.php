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

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Flarum\Http\UrlGenerator;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

/**
 * Points every tracked link in a post at the click-tracking route and attaches
 * the attributes the badge and the realtime updater read.
 *
 * Runs on each render rather than being baked into the stored XML, so a change
 * to the click count, the display threshold or the tracking settings shows up
 * without re-saving any post.
 */
class RewriteTrackableLinks
{
    public function __construct(
        protected TrackableSourceRegistry $sources,
        protected TrackingUrlSigner $signer,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $urls,
    ) {
    }

    public function __invoke(
        Renderer $renderer,
        mixed $context,
        string $xml,
        ?ServerRequestInterface $request = null,
    ): string {
        if ($request === null) {
            return $xml;
        }

        if (! (bool) $this->settings->get('datlechin-link-clicks.enabled', true)) {
            return $xml;
        }

        if (! $context instanceof CommentPost) {
            return $xml;
        }

        if ((bool) ($context->link_clicks_disabled ?? false)) {
            // Author/admin opted this post out. Skip rewriting so links
            // render with their original URLs and no badge appears.
            return $xml;
        }

        $links = $this->loadLinks($context);
        if ($links->isEmpty()) {
            return $xml;
        }

        $renderContext = new RenderContext(
            post: $context,
            minDisplayCount: (int) $this->settings->get('datlechin-link-clicks.min_display_count', 1),
        );

        foreach ($this->sources->all() as $source) {
            $forSource = $links->where('source', $source->key());

            if ($forSource->isEmpty()) {
                continue;
            }

            $xml = $this->rewriteSource($xml, $source, $forSource->keyBy('url_hash'), $renderContext);
        }

        return $xml;
    }

    /**
     * @param Collection<string, PostLink> $linksByHash
     */
    private function rewriteSource(
        string $xml,
        TrackableSource $source,
        Collection $linksByHash,
        RenderContext $context,
    ): string {
        return Utils::replaceAttributes(
            $xml,
            $source->tagName(),
            function (array $attrs) use ($source, $linksByHash, $context): array {
                $hash = $source->identify($attrs);
                if ($hash === null) {
                    return $attrs;
                }

                /** @var PostLink|null $link */
                $link = $linksByHash->get($hash);
                if ($link === null) {
                    return $attrs;
                }

                return $source->apply($attrs, $link, $this->trackingUrl($link), $context);
            },
        );
    }

    private function trackingUrl(PostLink $link): string
    {
        // UrlGenerator::route() only fills path placeholders; extra
        // parameters are dropped, not appended as query string. The
        // token is base64url-safe so no URL-encoding is needed.
        return $this->urls->to('forum')->route('datlechin-link-clicks.track')
            .'?u='.$this->signer->sign($link->id);
    }

    /**
     * @return Collection<int, PostLink>
     */
    private function loadLinks(CommentPost $post): Collection
    {
        /** @var Collection<int, PostLink> $links */
        $links = $post->relationLoaded('links')
            ? $post->getRelation('links')
            : $post->links()->get();

        return $links;
    }
}
