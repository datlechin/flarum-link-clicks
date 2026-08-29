<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Console;

use Datlechin\LinkClicks\Service\PostLinkSyncer;
use Flarum\Post\CommentPost;
use Illuminate\Console\Command;

/**
 * `php flarum link-clicks:backfill`. Populates `post_links` rows for posts
 * that existed before the extension was enabled, so their links get tracked
 * and counted on the next render. Idempotent: rows with a matching
 * (post_id, source, url_hash) are left in place.
 *
 * Goes through the same {@see PostLinkSyncer} as the Posted/Revised listener,
 * so a backfilled post ends up with exactly the rows it would have got had the
 * extension been enabled when it was written — every registered source
 * included, not just plain URLs.
 */
class BackfillCommand extends Command
{
    protected $signature = 'link-clicks:backfill {--chunk=500 : Posts to process per batch} {--from-id= : Resume from this post ID}';

    protected $description = 'Populate post_links rows for posts that existed before the extension was enabled.';

    public function handle(PostLinkSyncer $syncer): int
    {
        $chunkSize = (int) $this->option('chunk');
        $fromId = $this->option('from-id') !== null ? (int) $this->option('from-id') : 0;

        $totalPosts = 0;
        $totalRows = 0;

        $query = CommentPost::query()->where('id', '>=', $fromId)->orderBy('id');
        $expectedPosts = (clone $query)->count();
        $bar = $this->output->createProgressBar($expectedPosts);
        $bar->start();

        $query->chunkById($chunkSize, function ($posts) use ($syncer, &$totalPosts, &$totalRows, $bar) {
            foreach ($posts as $post) {
                $totalPosts++;
                $bar->advance();

                // Never delete orphans here: a backfill run walks historical
                // posts, and a target this run can't resolve (a source whose
                // extension is currently disabled) must not take existing rows
                // with it.
                $totalRows += $syncer->sync($post, deleteOrphans: false);
            }
        }, 'id', 'id');

        $bar->finish();
        $this->newLine();

        $this->info("Scanned {$totalPosts} posts, inserted {$totalRows} new post_links rows.");

        return self::SUCCESS;
    }
}
