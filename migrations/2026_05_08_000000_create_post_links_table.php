<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('post_links', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('post_id')->unsigned();
    $table->integer('discussion_id')->unsigned();
    $table->text('url');
    $table->char('url_hash', 64);
    $table->boolean('is_internal')->default(false);
    $table->boolean('is_attachment')->default(false);
    $table->unsignedInteger('clicks_count')->default(0);
    $table->timestamp('first_seen_at')->useCurrent();
    $table->timestamp('last_clicked_at')->nullable();

    $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
    $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');

    $table->unique(['post_id', 'url_hash']);
    $table->index('url_hash');
    $table->index(['discussion_id', 'clicks_count']);
});
