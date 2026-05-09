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
        $schema->table('post_links', function (Blueprint $table) {
            $table->string('domain', 255)->nullable()->index('post_links_domain_idx');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('post_links', function (Blueprint $table) {
            $table->dropIndex('post_links_domain_idx');
            $table->dropColumn('domain');
        });
    },
];
