<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Adds the source discriminator so `post_links` can hold more than plain URLs.
 *
 * `source` is NOT NULL with a constant default, so every pre-existing row
 * becomes 'url' without a backfill pass: MySQL 8.0.12+/MariaDB 10.3+ do this
 * with ALGORITHM=INSTANT, Postgres 11+ stores the default as column metadata,
 * and SQLite rewrites nothing. `label` carries a display name for sources
 * whose URL isn't meaningful to read (a tag mention shows "#support", not
 * "example.com/t/support"); it stays null for URL rows, which keep deriving
 * their display from the URL itself.
 *
 * `source_id` is the id of whatever the row points at — a tag, a user, a post
 * — so a click can resolve its destination live instead of trusting a URL that
 * was correct when the post was written. Deliberately carries no foreign key:
 * a forum can install this extension before flarum/mentions or flarum/tags
 * exist, and nothing re-runs a migration when a peer extension shows up later.
 * Null for plain URLs, which are their own identity.
 *
 * The unique key widens to include `source`. Hashes are already namespaced per
 * source, so this isn't strictly load-bearing, but a uniqueness rule that says
 * what it means beats one that relies on hash inputs never colliding.
 */
return [
    'up' => function (Builder $schema) {
        $schema->table('post_links', function (Blueprint $table) {
            $table->string('source', 32)->default('url');
            $table->string('label', 255)->nullable();
            $table->unsignedInteger('source_id')->nullable();
        });

        // Create the wider key before dropping the old one, never the other way
        // round. MySQL and MariaDB require an index whose first column is the
        // foreign key's, and `post_id` has one; dropping the only such index
        // fails with "needed in a foreign key constraint". Both of these lead
        // with `post_id`, so doing it in this order leaves the constraint
        // covered throughout.
        $schema->table('post_links', function (Blueprint $table) {
            $table->unique(['post_id', 'source', 'url_hash'], 'post_links_post_source_hash_uq');
        });

        $schema->table('post_links', function (Blueprint $table) {
            $table->dropUnique('post_links_post_id_url_hash_unique');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_links', function (Blueprint $table) {
            $table->unique(['post_id', 'url_hash'], 'post_links_post_id_url_hash_unique');
        });

        $schema->table('post_links', function (Blueprint $table) {
            $table->dropUnique('post_links_post_source_hash_uq');
        });

        $schema->table('post_links', function (Blueprint $table) {
            $table->dropColumn(['source', 'label', 'source_id']);
        });
    },
];
