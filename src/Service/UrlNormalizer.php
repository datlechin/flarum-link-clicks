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

use Datlechin\LinkClicks\ValueObject\NormalizedUrl;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;

class UrlNormalizer
{
    /**
     * Default tracking params stripped from canonical URLs. Both literal
     * names and wildcard patterns ending in `*` are supported (e.g.
     * `utm_*` strips every `utm_xyz`). Admin can override via the
     * `tracking_params_strip` setting (one entry per line, or comma-
     * separated). Defaults are the union of what Discourse's
     * cooked_post_processor normalizes plus the most common platform
     * tracking params we've seen pasted into forum posts.
     */
    private const DEFAULT_TRACKING_PATTERNS = [
        'utm_*',
        'fbclid',
        'gclid',
        'gbraid',
        'wbraid',
        'dclid',
        'msclkid',
        'yclid',
        'igshid',
        '_ga',
        '_gl',
        'mc_cid',
        'mc_eid',
        'ref',
        'ref_src',
        'ref_url',
    ];

    /** @var list<string>|null */
    private ?array $cachedPatterns = null;
    private ?string $cachedForumHost = null;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Config $config,
    ) {
    }

    public function normalize(string $rawUrl): ?NormalizedUrl
    {
        $parts = parse_url($rawUrl);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], strict: true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $authority = $this->buildAuthority($host, $scheme, $parts['port'] ?? null);
        $rawPath = $parts['path'] ?? '';
        $path = $this->normalizePath($rawPath, $host);
        $query = $this->normalizeQuery($parts['query'] ?? '');

        $canonical = $scheme.'://'.$authority.$path.($query !== '' ? '?'.$query : '');

        return new NormalizedUrl(
            value: $canonical,
            hash: hash('sha256', $canonical),
            host: $host,
            isAttachment: $this->isForumHost($host) && $this->isAttachmentPath($rawPath),
        );
    }

    private function buildAuthority(string $host, string $scheme, int|string|null $port): string
    {
        if ($port === null) {
            return $host;
        }

        $port = (int) $port;
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            return $host;
        }

        return $host.':'.$port;
    }

    private function normalizePath(string $path, string $host): string
    {
        if ($path === '' || $path === '/') {
            return '';
        }

        if ($this->isForumHost($host)) {
            $path = $this->canonicalizeForumPath($path);
        }

        return rtrim($path, '/');
    }

    /**
     * Strip the slug from `/d/{id}-{slug}` and `/d/{id}-{slug}/{near}` discussion
     * URLs. Slugs are derived from the discussion title and change whenever a
     * discussion is renamed; matching by ID alone keeps the same canonical hash
     * across renames so click aggregates don't fragment.
     *
     * The trailing `/{near}` (post-jump anchor) is preserved because clicks
     * targeting different posts in a discussion are semantically distinct.
     */
    private function canonicalizeForumPath(string $path): string
    {
        return preg_replace('#^/d/(\d+)(?:-[^/]+)?(/.*)?$#', '/d/$1$2', $path) ?? $path;
    }

    private function isForumHost(string $host): bool
    {
        $forumHost = $this->forumHost();
        return $forumHost !== '' && $host === $forumHost;
    }

    /**
     * Recognise URLs served by Flarum's default uploads disk (the
     * `assets/files/` prefix). Operators using a custom disk or third-party
     * upload extension (fof/upload) can extend this via the
     * `attachment_path_prefixes` setting (one prefix per line).
     */
    private function isAttachmentPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        foreach ($this->attachmentPrefixes() as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function attachmentPrefixes(): array
    {
        static $defaults = ['/assets/files/'];

        $custom = (string) $this->settings->get('datlechin-link-clicks.attachment_path_prefixes', '');
        if ($custom === '') {
            return $defaults;
        }

        $extra = array_filter(array_map(
            fn (string $p) => trim($p),
            preg_split('/[,\r\n]+/', $custom) ?: [],
        ));

        return array_values(array_unique([...$defaults, ...$extra]));
    }

    private function forumHost(): string
    {
        return $this->cachedForumHost ??= strtolower(parse_url($this->config->url(), PHP_URL_HOST) ?? '');
    }

    /**
     * Parse the query string ourselves rather than via `parse_str()`, which
     * mangles keys containing dots or brackets (`?v=1.5` → `v_5`). That
     * mangling would silently corrupt the canonical URL we redirect to.
     */
    private function normalizeQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $patterns = $this->trackingPatterns();

        $pairs = [];
        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $key = urldecode($key);
            if ($key === '' || $this->matchesPattern($key, $patterns)) {
                continue;
            }
            $pairs[$key][] = $value;
        }

        if ($pairs === []) {
            return '';
        }

        ksort($pairs);

        $rebuilt = [];
        foreach ($pairs as $key => $values) {
            sort($values);
            foreach ($values as $value) {
                $rebuilt[] = rawurlencode($key).($value !== '' ? '='.$value : '');
            }
        }

        return implode('&', $rebuilt);
    }

    /**
     * @return list<string>
     */
    private function trackingPatterns(): array
    {
        if ($this->cachedPatterns !== null) {
            return $this->cachedPatterns;
        }

        $custom = (string) $this->settings->get('datlechin-link-clicks.tracking_params_strip', '');
        if ($custom === '') {
            return $this->cachedPatterns = self::DEFAULT_TRACKING_PATTERNS;
        }

        $extra = array_filter(array_map(
            fn (string $p) => strtolower(trim($p)),
            preg_split('/[,\r\n]+/', $custom) ?: [],
        ));

        return $this->cachedPatterns = array_values(array_unique([...self::DEFAULT_TRACKING_PATTERNS, ...$extra]));
    }

    /**
     * @param list<string> $patterns
     */
    private function matchesPattern(string $key, array $patterns): bool
    {
        $keyLower = strtolower($key);
        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '*')) {
                $prefix = substr($pattern, 0, -1);
                if ($prefix !== '' && str_starts_with($keyLower, $prefix)) {
                    return true;
                }
            } elseif ($keyLower === $pattern) {
                return true;
            }
        }

        return false;
    }
}
