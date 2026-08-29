<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Source;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\LinkExtractor;
use Datlechin\LinkClicks\Service\UrlNormalizer;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Foundation\Config;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Plain `http(s)` links written in a post, the `<URL>` tag the formatter
 * produces for both pasted URLs and `[text](url)` markdown.
 *
 * This is the original and still the default source; everything it does here
 * is the behaviour Link Clicks shipped before sources existed.
 */
class UrlSource implements TrackableSource
{
    public function __construct(
        protected LinkExtractor $extractor,
        protected UrlNormalizer $normalizer,
        protected SettingsRepositoryInterface $settings,
        protected TranslatorInterface $translator,
        protected Config $config,
    ) {
    }

    public function key(): string
    {
        return PostLink::SOURCE_URL;
    }

    public function tagName(): string
    {
        return 'URL';
    }

    public function extract(string $parsedXml): array
    {
        $forumHost = $this->forumHost();
        $targets = [];

        foreach ($this->extractor->extract($parsedXml) as $hash => $normalized) {
            $isInternal = $forumHost !== '' && $normalized->host === $forumHost;

            $targets[$hash] = new TrackedTarget(
                hash: $hash,
                url: $normalized->value,
                isInternal: $isInternal,
                isAttachment: $normalized->isAttachment,
                domain: $normalized->host,
            );
        }

        return $targets;
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        if (! $target->isInternal) {
            return true;
        }

        // Attachments live on the forum host but are conceptually a download
        // resource, not a navigation link. We track them even when
        // track_internal is off so admins always see file traffic.
        if ($target->isAttachment) {
            return true;
        }

        return (bool) $this->settings->get('datlechin-link-clicks.track_internal', false);
    }

    public function forwardedAttributes(): array
    {
        return [
            'data-clicks',
            'data-post-id',
            'data-url-id',
            'data-custom-title',
            'class',
            'target',
            'rel',
        ];
    }

    public function identify(array $attrs): ?string
    {
        $rawUrl = $attrs['url'] ?? '';
        if ($rawUrl === '') {
            return null;
        }

        return $this->normalizer->normalize($rawUrl)?->hash;
    }

    public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
    {
        // Deliberately not `url`: core reads that attribute after us to work
        // out where the link goes, and it must keep seeing the real
        // destination. See ConfigureTrackableTemplates::makeHrefTrackable().
        $attrs['data-lc-href'] = $trackingUrl;
        $attrs['data-post-id'] = (string) $context->post->id;
        $attrs['data-url-id'] = (string) $link->id;
        $attrs['class'] = trim(($attrs['class'] ?? '').' LinkClicks-link');

        if ((bool) $this->settings->get('datlechin-link-clicks.open_in_new_window', false)) {
            // Forces the browser to open the destination in a new tab.
            // `noopener noreferrer` keeps the new tab from reaching back
            // into window.opener and stops the destination from seeing
            // the source URL via Referer.
            $attrs['target'] = '_blank';
            $attrs['rel'] = trim(($attrs['rel'] ?? '').' noopener noreferrer');
        }

        $hasCustomTitle = isset($attrs['title']) && $attrs['title'] !== '';
        if ($hasCustomTitle) {
            // Marker for the realtime JS so it knows not to overwrite the
            // user-authored title on later count updates.
            $attrs['data-custom-title'] = '1';
        }

        if ($link->clicks_count >= $context->minDisplayCount) {
            $attrs['data-clicks'] = (string) $link->clicks_count;

            if (! $hasCustomTitle) {
                $attrs['title'] = (string) $this->translator->trans(
                    'datlechin-link-clicks.forum.link_tooltip',
                    ['count' => $link->clicks_count],
                );
            }
        }

        return $attrs;
    }

    public function resolveTarget(PostLink $link): ?string
    {
        // Defence-in-depth: validate the redirect destination scheme. The URL
        // is normalised to http(s) at write time, but re-checking here closes
        // the door to any future code path that bypasses the normaliser.
        $scheme = parse_url($link->url, PHP_URL_SCHEME);

        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], strict: true)) {
            return null;
        }

        return $link->url;
    }

    private function forumHost(): string
    {
        return strtolower(parse_url($this->config->url(), PHP_URL_HOST) ?? '');
    }
}
