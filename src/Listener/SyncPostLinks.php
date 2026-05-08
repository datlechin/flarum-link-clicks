<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Listener;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\LinkExtractor;
use Datlechin\LinkClicks\Service\TagOptOut;
use Flarum\Foundation\Config;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\QueryException;

/**
 * On `Posted` and `Revised`, walks the post's parsed XML for `<URL>` tags and
 * upserts a `post_links` row for each unique normalized URL.
 *
 * On `Revised`, deletes rows for URLs that were edited out of the post (links
 * still present in the post are always preserved, regardless of the
 * `track_internal` setting; that setting only gates row *creation*, never
 * deletion).
 */
class SyncPostLinks
{
    public function __construct(
        protected LinkExtractor $extractor,
        protected Config $config,
        protected SettingsRepositoryInterface $settings,
        protected TagOptOut $tagOptOut,
    ) {
    }

    public function handle(Posted|Revised $event): void
    {
        $this->sync($event->post, deleteOrphans: $event instanceof Revised);
    }

    /**
     * Re-extract and persist the post_links rows for a post. Public so the
     * PostResource `linkClicksDisabled` field's save callback can trigger a
     * re-sync when an admin/author toggles tracking back on (toggling off
     * just deletes existing rows).
     *
     * @param bool $deleteOrphans When true, post_links rows for URLs no longer
     *                            present in the post body are deleted. Set on
     *                            Revised events and manual re-syncs;
     *                            disabled for first-Posted (nothing to orphan).
     */
    public function sync(Post $post, bool $deleteOrphans = true): void
    {
        if (! (bool) $this->settings->get('datlechin-link-clicks.enabled', true)) {
            return;
        }

        if (! $post instanceof CommentPost) {
            return;
        }

        if ((bool) ($post->link_clicks_disabled ?? false)) {
            // Author opted this post out of tracking. We deliberately keep
            // existing post_links rows here so signed tokens in already-
            // rendered HTML still resolve to a real link in TrackClickController
            // (which separately checks the disabled flag and skips recording).
            // RewriteLinksForTracking also bails out for disabled posts so
            // re-renders stop emitting tracking URLs.
            return;
        }

        if ($this->tagOptOut->isDiscussionOptedOut($post->discussion_id)) {
            return;
        }

        $xml = $post->parsed_content;
        if ($xml === null || $xml === '') {
            return;
        }

        $extracted = $this->extractor->extract($xml);
        $forumHost = $this->forumHost();
        $trackInternal = (bool) $this->settings->get('datlechin-link-clicks.track_internal', false);
        $now = Carbon::now();

        // All URL hashes currently in the post. Used to decide which rows to
        // preserve. Independent of the track_internal filter so that
        // disabling internal-link tracking later doesn't cause existing
        // external rows to get deleted.
        $allHashes = array_keys($extracted);

        foreach ($extracted as $hash => $normalized) {
            $isInternal = $forumHost !== '' && $normalized->host === $forumHost;
            // Attachments live on the forum host but are conceptually a
            // download resource, not a navigation link. We track them even
            // when track_internal is off so admins always see file traffic.
            if ($isInternal && ! $trackInternal && ! $normalized->isAttachment) {
                continue;
            }

            try {
                PostLink::query()->firstOrCreate(
                    [
                        'post_id' => $post->id,
                        'url_hash' => $hash,
                    ],
                    [
                        'discussion_id' => $post->discussion_id,
                        'url' => $normalized->value,
                        'is_internal' => $isInternal,
                        'is_attachment' => $normalized->isAttachment,
                        'first_seen_at' => $now,
                    ],
                );
            } catch (QueryException $e) {
                // Concurrent save can race the SELECT-then-INSERT inside
                // firstOrCreate against the unique (post_id, url_hash)
                // constraint. The other writer won; our row already exists,
                // so the desired state is satisfied.
                if (! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        if ($deleteOrphans) {
            $deleteQuery = PostLink::query()->where('post_id', $post->id);
            if ($allHashes !== []) {
                $deleteQuery->whereNotIn('url_hash', $allHashes);
            }
            // When $allHashes is empty (post had all links removed), every
            // row for this post is deleted intentionally.
            $deleteQuery->delete();
        }
    }

    private function forumHost(): string
    {
        return strtolower(parse_url($this->config->url(), PHP_URL_HOST) ?? '');
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // SQLSTATE 23000 (integrity constraint violation) covers MySQL,
        // MariaDB, and PostgreSQL unique-violation errors uniformly.
        return $e->getCode() === '23000';
    }
}
