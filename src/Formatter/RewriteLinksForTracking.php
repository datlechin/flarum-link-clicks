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

use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Datlechin\LinkClicks\Service\UrlNormalizer;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class RewriteLinksForTracking
{
    public function __construct(
        protected TrackingUrlSigner $signer,
        protected UrlNormalizer $normalizer,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $urls,
        protected TranslatorInterface $translator,
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

        $linksByHash = $this->loadLinksByHash($context);
        if ($linksByHash->isEmpty()) {
            return $xml;
        }

        $minDisplay = (int) $this->settings->get('datlechin-link-clicks.min_display_count', 1);

        return Utils::replaceAttributes($xml, 'URL', function (array $attrs) use ($linksByHash, $context, $minDisplay): array {
            $rawUrl = $attrs['url'] ?? '';
            if ($rawUrl === '') {
                return $attrs;
            }

            $normalized = $this->normalizer->normalize($rawUrl);
            if ($normalized === null) {
                return $attrs;
            }

            /** @var PostLink|null $postLink */
            $postLink = $linksByHash->get($normalized->hash);
            if ($postLink === null) {
                return $attrs;
            }

            $token = $this->signer->sign($postLink->id);

            // UrlGenerator::route() only fills path placeholders; extra
            // parameters are dropped, not appended as query string. The
            // token is base64url-safe so no URL-encoding is needed.
            $attrs['url'] = $this->urls->to('forum')
                ->route('datlechin-link-clicks.track').'?u='.$token;
            $attrs['data-post-id'] = (string) $context->id;
            $attrs['data-url-id'] = (string) $postLink->id;
            $attrs['class'] = trim(($attrs['class'] ?? '').' LinkClicks-link');

            $hasCustomTitle = isset($attrs['title']) && $attrs['title'] !== '';
            if ($hasCustomTitle) {
                // Marker for the realtime JS so it knows not to overwrite the
                // user-authored title on later count updates.
                $attrs['data-custom-title'] = '1';
            }

            if ($postLink->clicks_count >= $minDisplay) {
                $attrs['data-clicks'] = (string) $postLink->clicks_count;

                if (! $hasCustomTitle) {
                    $attrs['title'] = (string) $this->translator->trans(
                        'datlechin-link-clicks.forum.link_tooltip',
                        ['count' => $postLink->clicks_count],
                    );
                }
            }

            return $attrs;
        });
    }

    /**
     * @return Collection<int, PostLink>
     */
    private function loadLinksByHash(CommentPost $post): Collection
    {
        /** @var Collection<int, PostLink> $links */
        $links = $post->relationLoaded('links')
            ? $post->getRelation('links')
            : $post->links()->get();

        return $links->keyBy('url_hash');
    }
}
