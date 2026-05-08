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

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Buckets a raw User-Agent string into a coarse family label.
 *
 * Built-in bot fragments cover the common crawlers; admins can extend the
 * list via the `bot_user_agents` setting (one fragment per line; commas
 * are also tolerated for paste-friendliness). The two lists are merged so
 * admin-supplied entries can never weaken default bot detection.
 */
class UserAgentClassifier
{
    private const BUILTIN_BOT_FRAGMENTS = [
        'bot', 'crawler', 'spider', 'curl', 'wget',
        'python-requests', 'httpclient', 'headlesschrome',
        'facebookexternalhit', 'slackbot', 'twitterbot',
    ];

    private const BROWSER_FAMILIES = ['edg', 'chrome', 'firefox', 'safari'];

    /** @var list<string>|null */
    private ?array $cachedFragments = null;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function classify(string $rawUa): string
    {
        if ($rawUa === '') {
            return 'other';
        }

        $lower = strtolower($rawUa);

        foreach ($this->botFragments() as $fragment) {
            if (str_contains($lower, $fragment)) {
                return 'bot';
            }
        }

        foreach (self::BROWSER_FAMILIES as $family) {
            if (str_contains($lower, $family)) {
                return $family === 'edg' ? 'edge' : $family;
            }
        }

        return 'other';
    }

    /**
     * @return list<string>
     */
    private function botFragments(): array
    {
        if ($this->cachedFragments !== null) {
            return $this->cachedFragments;
        }

        $custom = (string) $this->settings->get('datlechin-link-clicks.bot_user_agents', '');
        if ($custom === '') {
            return $this->cachedFragments = self::BUILTIN_BOT_FRAGMENTS;
        }

        $extra = array_filter(array_map(
            fn (string $f) => strtolower(trim($f)),
            preg_split('/[,\r\n]+/', $custom) ?: [],
        ));

        return $this->cachedFragments = array_values(array_unique([...self::BUILTIN_BOT_FRAGMENTS, ...$extra]));
    }
}
