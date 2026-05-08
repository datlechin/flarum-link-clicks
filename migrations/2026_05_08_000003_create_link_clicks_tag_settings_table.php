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

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('tags')) {
            return;
        }

        $schema->create('link_clicks_tag_settings', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned()->primary();
            $table->boolean('disabled')->default(false);

            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('link_clicks_tag_settings');
    },
];
