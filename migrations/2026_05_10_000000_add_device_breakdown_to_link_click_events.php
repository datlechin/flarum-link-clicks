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
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('link_click_events', function (Blueprint $table) {
            $table->string('device_class', 16)->nullable();
            $table->string('browser_family', 16)->nullable();
            $table->index(['device_class', 'browser_family'], 'lce_device_browser_idx');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('link_click_events', function (Blueprint $table) {
            $table->dropIndex('lce_device_browser_idx');
            $table->dropColumn(['device_class', 'browser_family']);
        });
    },
];
