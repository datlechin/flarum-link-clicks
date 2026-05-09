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

return Migration::createTable('link_click_daily', function (Blueprint $table) {
    $table->date('date');
    $table->integer('post_link_id')->unsigned();
    $table->integer('count')->unsigned()->default(0);

    $table->primary(['date', 'post_link_id'], 'link_click_daily_pk');
    $table->foreign('post_link_id')->references('id')->on('post_links')->onDelete('cascade');
    $table->index('date', 'lcd_date_idx');
});
