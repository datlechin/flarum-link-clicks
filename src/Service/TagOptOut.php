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

use Illuminate\Database\ConnectionInterface;

/**
 * Read/write helper for the `link_clicks_tag_settings` pivot table.
 *
 * The table is only created when `flarum-tags` was installed at the time our
 * migration ran, so every method here gracefully no-ops (or returns false)
 * when it doesn't exist. Avoids forcing every caller to repeat the schema
 * check.
 */
class TagOptOut
{
    private ?bool $tableExists = null;

    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    public function isTagDisabled(int $tagId): bool
    {
        if (! $this->tableAvailable()) {
            return false;
        }

        return (bool) $this->db->table('link_clicks_tag_settings')
            ->where('tag_id', $tagId)
            ->value('disabled');
    }

    public function setTagDisabled(int $tagId, bool $disabled): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        if ($disabled) {
            $this->db->table('link_clicks_tag_settings')
                ->updateOrInsert(['tag_id' => $tagId], ['disabled' => true]);
        } else {
            $this->db->table('link_clicks_tag_settings')
                ->where('tag_id', $tagId)
                ->delete();
        }
    }

    public function isDiscussionOptedOut(int $discussionId): bool
    {
        if (! $this->tableAvailable()) {
            return false;
        }

        return $this->db->table('link_clicks_tag_settings')
            ->join('discussion_tag', 'discussion_tag.tag_id', '=', 'link_clicks_tag_settings.tag_id')
            ->where('discussion_tag.discussion_id', $discussionId)
            ->where('link_clicks_tag_settings.disabled', true)
            ->exists();
    }

    private function tableAvailable(): bool
    {
        return $this->tableExists ??= $this->db->getSchemaBuilder()->hasTable('link_clicks_tag_settings');
    }
}
