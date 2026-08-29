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

use Datlechin\LinkClicks\Service\PostLinkSyncer;
use Datlechin\LinkClicks\Service\TagOptOut;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * On `Posted` and `Revised`, decides whether a post should be tracked at all
 * and hands it to {@see PostLinkSyncer}, which writes a `post_links` row for
 * every trackable target each registered source finds.
 *
 * On `Revised`, rows for targets that were edited out of the post are deleted.
 */
class SyncPostLinks
{
    public function __construct(
        protected PostLinkSyncer $syncer,
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
     * @param bool $deleteOrphans When true, post_links rows for targets no
     *                            longer present in the post body are deleted.
     *                            Set on Revised events and manual re-syncs;
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
            // RewriteTrackableLinks also bails out for disabled posts so
            // re-renders stop emitting tracking URLs.
            return;
        }

        if ($this->tagOptOut->isDiscussionOptedOut($post->discussion_id)) {
            return;
        }

        $this->syncer->sync($post, $deleteOrphans);
    }
}
