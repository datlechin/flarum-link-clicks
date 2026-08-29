<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Api\Controller;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Narrows an aggregate over `link_click_events` / `link_click_daily` to a
 * single source.
 *
 * Those two tables key on `post_link_id` and carry no source column of their
 * own, so the discriminator has to be reached through `post_links`. Without
 * this, the daily chart and the heatmap would silently blend hashtag clicks
 * into the link totals with no way to tell them apart.
 */
trait ScopesBySource
{
    protected function scopeToSource(Builder $query, Request $request, string $table): Builder
    {
        $source = Arr::get($request->getQueryParams(), 'filter.source');

        if (! is_string($source) || $source === '') {
            return $query;
        }

        return $query->whereIn(
            $table.'.post_link_id',
            fn (Builder $sub) => $sub->select('id')->from('post_links')->where('source', $source),
        );
    }
}
