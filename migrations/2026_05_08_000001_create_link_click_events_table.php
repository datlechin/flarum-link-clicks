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

return Migration::createTable('link_click_events', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('post_link_id')->unsigned();
    $table->integer('user_id')->unsigned()->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->boolean('counted');
    $table->timestamp('clicked_at')->useCurrent();

    $table->foreign('post_link_id')->references('id')->on('post_links')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

    // Explicit shorter index names because the auto-generated ones combine
    // table + every column + `_index` and exceed MySQL 5.7's 64-character
    // identifier limit when a table prefix is also configured.
    $table->index(['user_id', 'post_link_id', 'clicked_at'], 'lce_user_link_time_idx');
    $table->index(['post_link_id', 'ip_address', 'clicked_at'], 'lce_link_ip_time_idx');
    $table->index('clicked_at', 'lce_clicked_at_idx');
});
