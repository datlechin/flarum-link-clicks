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
use Illuminate\Database\ConnectionInterface;

/**
 * Reads the HMAC key from settings, generating one on first access if missing.
 * Compare-and-swap pattern: after writing, re-read to detect a worker that
 * raced and won; that worker's value is the canonical one.
 *
 * No in-process cache is used: SettingsRepositoryInterface already caches
 * within a single request, and avoiding our own cache eliminates the failure
 * mode where a losing worker keeps signing tokens with a now-stale key for
 * the rest of its FPM lifetime.
 */
class HmacKeyProvider
{
    private const SETTING_KEY = 'datlechin-link-clicks.hmac_key';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ConnectionInterface $db,
    ) {
    }

    public function get(): string
    {
        $key = $this->settings->get(self::SETTING_KEY);
        if (is_string($key) && $key !== '') {
            return $key;
        }

        return $this->generateOrRead();
    }

    /**
     * Generate a key while serialised against other workers via a transactional
     * read-after-write. The settings table has its own primary key on `key`,
     * so a duplicate insert from a racing worker fails; we then re-read the
     * canonical value that worker committed.
     */
    private function generateOrRead(): string
    {
        $candidate = bin2hex(random_bytes(32));

        $this->db->transaction(function () use ($candidate) {
            $existing = $this->db->table('settings')
                ->where('key', self::SETTING_KEY)
                ->lockForUpdate()
                ->value('value');

            if ($existing === null) {
                $this->settings->set(self::SETTING_KEY, $candidate);
            }
        });

        // Re-read so a racing worker's value (committed before our lock) is
        // what we return, not our discarded $candidate.
        $key = $this->settings->get(self::SETTING_KEY);
        if (! is_string($key) || $key === '') {
            // Should not happen: we just wrote it (or saw an existing row).
            // Defensive fallback so the request can still proceed.
            return $candidate;
        }

        return $key;
    }
}
