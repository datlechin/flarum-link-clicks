<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Gdpr;

use Datlechin\LinkClicks\LinkClickEvent;
use Datlechin\LinkClicks\PostLink;
use Flarum\Gdpr\Data\Type;

/**
 * Plugs `link_click_events` into the flarum-gdpr export and erasure pipeline.
 *
 * Export dumps every event row authored by the user. Anonymise drops the
 * three PII fields (user_id, ip_address, user_agent). Delete removes the
 * rows and decrements the per-link aggregate so the badge count stays in
 * sync. Only loaded when `flarum-gdpr` is enabled (gated via Conditional
 * in extend.php).
 */
class LinkClicks extends Type
{
    public static function dataType(): string
    {
        return 'Link Clicks';
    }

    public static function exportDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-link-clicks.gdpr.export_description');
    }

    public static function anonymizeDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-link-clicks.gdpr.anonymize_description');
    }

    public static function deleteDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-link-clicks.gdpr.delete_description');
    }

    /**
     * @return list<string>
     */
    public static function piiFields(): array
    {
        return ['ip_address', 'user_agent'];
    }

    public function export(): ?array
    {
        $rows = LinkClickEvent::query()
            ->where('user_id', $this->user->id)
            ->orderBy('clicked_at')
            ->get()
            ->toArray();

        if ($rows === []) {
            return null;
        }

        return [['link-clicks/events.json' => $this->encodeForExport($rows)]];
    }

    public function anonymize(): void
    {
        LinkClickEvent::query()
            ->where('user_id', $this->user->id)
            ->update([
                'user_id' => null,
                'ip_address' => null,
                'user_agent' => null,
            ]);
    }

    public function delete(): void
    {
        LinkClickEvent::query()
            ->getConnection()
            ->transaction(function () {
                $countsByLink = LinkClickEvent::query()
                    ->where('user_id', $this->user->id)
                    ->where('counted', true)
                    ->selectRaw('post_link_id, COUNT(*) as cnt')
                    ->groupBy('post_link_id')
                    ->pluck('cnt', 'post_link_id');

                LinkClickEvent::query()
                    ->where('user_id', $this->user->id)
                    ->delete();

                foreach ($countsByLink as $linkId => $cnt) {
                    PostLink::query()
                        ->where('id', $linkId)
                        ->decrement('clicks_count', (int) $cnt);
                }
            });
    }
}
