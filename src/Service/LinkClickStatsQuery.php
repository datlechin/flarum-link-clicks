<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Service;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * Shared filter parsing and base query construction for the analytics
 * endpoints (paginated JSON list + full CSV export).
 *
 * Keeps the SQL definition of "an analytics row" in one place — both
 * endpoints must agree on how rows are filtered and joined, otherwise the
 * CSV would silently disagree with what the admin sees in the table.
 */
class LinkClickStatsQuery
{
    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{since?: Carbon, until?: Carbon, is_internal?: bool, is_attachment?: bool, tag?: string}
     *
     * @throws \InvalidArgumentException on malformed input (controller maps to 422)
     */
    public function parseFilter(array $raw): array
    {
        $out = [];

        foreach (['since', 'until'] as $key) {
            if (empty($raw[$key])) {
                continue;
            }
            try {
                $out[$key] = Carbon::parse($raw[$key]);
            } catch (\Exception) {
                throw new \InvalidArgumentException("filter[{$key}] is not a valid date.");
            }
        }
        if (isset($out['until'])) {
            $out['until'] = $out['until']->endOfDay();
        }

        foreach (['is_internal', 'is_attachment'] as $boolKey) {
            if (! isset($raw[$boolKey])) {
                continue;
            }
            $bool = filter_var($raw[$boolKey], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                throw new \InvalidArgumentException("filter[{$boolKey}] must be a boolean.");
            }
            $out[$boolKey] = $bool;
        }

        if (! empty($raw['tag'])) {
            $out['tag'] = (string) $raw['tag'];
        }

        return $out;
    }

    /**
     * @param array{since?: Carbon, until?: Carbon, is_internal?: bool, is_attachment?: bool, tag?: string} $filter
     */
    public function baseQuery(array $filter): Builder
    {
        $query = $this->db->table('post_links as pl')
            ->join('link_click_events as lce', 'lce.post_link_id', '=', 'pl.id')
            ->join('posts as p', 'p.id', '=', 'pl.post_id')
            ->where('lce.counted', true)
            ->where('p.link_clicks_disabled', false);

        if (isset($filter['since'])) {
            $query->where('lce.clicked_at', '>=', $filter['since']);
        }
        if (isset($filter['until'])) {
            $query->where('lce.clicked_at', '<=', $filter['until']);
        }
        if (isset($filter['is_internal'])) {
            $query->where('pl.is_internal', $filter['is_internal']);
        }
        if (isset($filter['is_attachment'])) {
            $query->where('pl.is_attachment', $filter['is_attachment']);
        }
        if (isset($filter['tag'])) {
            $query->whereIn('pl.discussion_id', function (Builder $q) use ($filter) {
                $q->select('discussion_id')
                    ->from('discussion_tag')
                    ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
                    ->where('tags.slug', $filter['tag']);
            });
        }

        return $query;
    }
}
