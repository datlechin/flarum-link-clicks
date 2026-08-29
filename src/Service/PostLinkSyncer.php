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

use Carbon\Carbon;
use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Post\CommentPost;
use Illuminate\Database\QueryException;

/**
 * Writes the `post_links` rows for a post, one source at a time.
 *
 * Shared by the `Posted`/`Revised` listener and the backfill command so the
 * two can't drift apart on which rows a post should have.
 */
class PostLinkSyncer
{
    public function __construct(
        protected TrackableSourceRegistry $sources,
    ) {
    }

    /**
     * @param  bool $deleteOrphans When true, rows for targets no longer present
     *                             in the post body are deleted. Deletion is
     *                             scoped to the sources that actually ran, so a
     *                             source whose extension is currently disabled
     *                             never loses its rows.
     * @return int  How many rows were created, for the backfill command's
     *              progress reporting.
     */
    public function sync(CommentPost $post, bool $deleteOrphans = true): int
    {
        $xml = $post->parsed_content;

        if ($xml === null || $xml === '') {
            return 0;
        }

        $now = Carbon::now();
        $created = 0;

        foreach ($this->sources->all() as $source) {
            $created += $this->syncSource($post, $source, $xml, $now, $deleteOrphans);
        }

        return $created;
    }

    private function syncSource(
        CommentPost $post,
        TrackableSource $source,
        string $xml,
        Carbon $now,
        bool $deleteOrphans,
    ): int {
        $extracted = $source->extract($xml);
        $created = 0;

        foreach ($extracted as $target) {
            if (! $source->shouldPersist($target)) {
                continue;
            }

            $created += $this->upsert($post, $source->key(), $target, $now) ? 1 : 0;
        }

        if (! $deleteOrphans) {
            return $created;
        }

        $deleteQuery = PostLink::query()
            ->where('post_id', $post->id)
            ->where('source', $source->key());

        // Every hash currently in the post, including targets shouldPersist()
        // declined. Keeping the declined ones here is deliberate: turning a
        // setting like track_internal off later must not delete rows that are
        // still referenced by the post body.
        if ($extracted !== []) {
            $deleteQuery->whereNotIn('url_hash', array_keys($extracted));
        }

        // When $extracted is empty (the post had every target of this kind
        // removed), every row for this post and source is deleted intentionally.
        $deleteQuery->delete();

        return $created;
    }

    /**
     * @return bool Whether a new row was created.
     */
    private function upsert(CommentPost $post, string $sourceKey, TrackedTarget $target, Carbon $now): bool
    {
        try {
            $link = PostLink::query()->firstOrCreate(
                [
                    'post_id' => $post->id,
                    'source' => $sourceKey,
                    'url_hash' => $target->hash,
                ],
                [
                    'discussion_id' => $post->discussion_id,
                    'url' => $target->url,
                    'label' => $target->label,
                    'source_id' => $target->sourceId,
                    'is_internal' => $target->isInternal,
                    'is_attachment' => $target->isAttachment,
                    'domain' => $target->domain,
                    'first_seen_at' => $now,
                ],
            );

            if ($link->wasRecentlyCreated) {
                return true;
            }

            $this->refreshDisplay($link, $target);

            return false;
        } catch (QueryException $e) {
            // Concurrent save can race the SELECT-then-INSERT inside
            // firstOrCreate against the unique (post_id, source, url_hash)
            // constraint. The other writer won; our row already exists,
            // so the desired state is satisfied.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Bring an existing row's display fields back in line with the target.
     *
     * The row's identity (its hash) is stable, but what it should *show* can
     * move underneath it: rows created before the `domain` column existed
     * still have it null, and a mentioned tag or user can be renamed long
     * after the post was written. Only writes when something actually
     * changed, so re-syncing an unchanged post stays read-only.
     */
    private function refreshDisplay(PostLink $link, TrackedTarget $target): void
    {
        $fresh = [
            'url' => $target->url,
            'label' => $target->label,
            'domain' => $target->domain,
        ];

        foreach ($fresh as $column => $value) {
            if ($link->{$column} !== $value) {
                $link->{$column} = $value;
            }
        }

        if ($link->isDirty()) {
            $link->save();
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // SQLSTATE 23000 (integrity constraint violation) covers MySQL,
        // MariaDB, and PostgreSQL unique-violation errors uniformly.
        return $e->getCode() === '23000';
    }
}
