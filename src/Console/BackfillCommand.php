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

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\LinkExtractor;
use Flarum\Foundation\Config;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * `php flarum link-clicks:backfill`. Populates `post_links` rows for posts
 * that existed before the extension was enabled, so their links get tracked
 * and counted on the next render. Idempotent: rows with a matching
 * (post_id, url_hash) are left in place.
 */
class BackfillCommand extends Command
{
    protected $signature = 'link-clicks:backfill {--chunk=500 : Posts to process per batch} {--from-id= : Resume from this post ID}';

    protected $description = 'Populate post_links rows for posts that existed before the extension was enabled.';

    public function handle(
        LinkExtractor $extractor,
        Config $config,
        SettingsRepositoryInterface $settings,
    ): int {
        $forumHost = strtolower(parse_url($config->url(), PHP_URL_HOST) ?? '');
        $trackInternal = (bool) $settings->get('datlechin-link-clicks.track_internal', false);
        $chunkSize = (int) $this->option('chunk');
        $fromId = $this->option('from-id') !== null ? (int) $this->option('from-id') : 0;

        $totalPosts = 0;
        $totalRows = 0;
        $now = Carbon::now();

        $query = CommentPost::query()->where('id', '>=', $fromId)->orderBy('id');
        $expectedPosts = (clone $query)->count();
        $bar = $this->output->createProgressBar($expectedPosts);
        $bar->start();

        $query->chunkById($chunkSize, function ($posts) use ($extractor, $forumHost, $trackInternal, $now, &$totalPosts, &$totalRows, $bar) {
            foreach ($posts as $post) {
                $totalPosts++;
                $bar->advance();

                $xml = $post->parsed_content;
                if ($xml === null || $xml === '') {
                    continue;
                }

                foreach ($extractor->extract($xml) as $hash => $normalized) {
                    $isInternal = $forumHost !== '' && $normalized->host === $forumHost;
                    if ($isInternal && ! $trackInternal) {
                        continue;
                    }

                    try {
                        $created = PostLink::query()->firstOrCreate(
                            ['post_id' => $post->id, 'url_hash' => $hash],
                            [
                                'discussion_id' => $post->discussion_id,
                                'url' => $normalized->value,
                                'is_internal' => $isInternal,
                                'is_attachment' => $normalized->isAttachment,
                                'domain' => $normalized->host,
                                'first_seen_at' => $now,
                            ],
                        );
                        if ($created->wasRecentlyCreated) {
                            $totalRows++;
                        } elseif ($created->domain !== $normalized->host) {
                            // Existing rows from before the domain column may
                            // have NULL; backfill it on encounter so the new
                            // grouping works without a separate migration.
                            $created->domain = $normalized->host;
                            $created->save();
                        }
                    } catch (QueryException $e) {
                        if ($e->getCode() !== '23000') {
                            throw $e;
                        }
                    }
                }
            }
        }, 'id', 'id');

        $bar->finish();
        $this->newLine();

        $this->info("Scanned {$totalPosts} posts, inserted {$totalRows} new post_links rows.");

        return self::SUCCESS;
    }
}
