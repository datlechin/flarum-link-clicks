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

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /api/trending-hashtags`. The hashtags whose click rate has risen most
 * sharply, for the forum-index widget.
 *
 * Ranked by velocity rather than lifetime total: a list of the same few
 * all-time favourites never changes and stops being worth reading, whereas a
 * tag that suddenly gets attention is the thing worth surfacing.
 *
 * Reads the daily rollup, which deliberately never covers today (its count is
 * still moving), so "recent" here means the last fully-rolled-up day measured
 * against the six before it.
 */
class ListTrendingHashtagsController implements RequestHandlerInterface
{
    private const LIMIT = 5;
    private const BASELINE_DAYS = 7;
    private const TTL_SECONDS = 600;

    public function __construct(
        protected ConnectionInterface $db,
        protected CacheRepository $cache,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        if (! (bool) $this->settings->get('datlechin-link-clicks.trending_enabled', true)) {
            return new JsonResponse([]);
        }

        $minClicks = (int) $this->settings->get('datlechin-link-clicks.trending_min_clicks', 5);

        // Nothing here varies per actor: hashtags are forum-wide, and the rows
        // carry no discussion the reader might not be allowed to see.
        $rows = $this->cache->remember(
            "datlechin-link-clicks.trending.{$minClicks}",
            self::TTL_SECONDS,
            fn () => $this->rank($minClicks),
        );

        return new JsonResponse($rows);
    }

    /**
     * @return list<array{id: int, label: string, url: string, count: int, velocity: float}>
     */
    private function rank(int $minClicks): array
    {
        $recentDay = Carbon::yesterday()->toDateString();
        $baselineStart = Carbon::yesterday()->subDays(self::BASELINE_DAYS - 1)->toDateString();
        $baselineEnd = Carbon::yesterday()->subDay()->toDateString();

        $recent = $this->db->table('link_click_daily')
            ->join('post_links', 'post_links.id', '=', 'link_click_daily.post_link_id')
            ->where('post_links.source', PostLink::SOURCE_TAG_MENTION)
            ->where('link_click_daily.date', $recentDay)
            ->groupBy('post_links.url_hash')
            ->selectRaw(
                $this->col('post_links.url_hash').' as url_hash, '
                .'MIN('.$this->col('post_links.id').') as id, '
                .'SUM('.$this->col('link_click_daily.count').') as recent'
            )
            ->get();

        if ($recent->isEmpty()) {
            return [];
        }

        $baseline = $this->db->table('link_click_daily')
            ->join('post_links', 'post_links.id', '=', 'link_click_daily.post_link_id')
            ->where('post_links.source', PostLink::SOURCE_TAG_MENTION)
            ->whereBetween('link_click_daily.date', [$baselineStart, $baselineEnd])
            ->groupBy('post_links.url_hash')
            ->selectRaw(
                $this->col('post_links.url_hash').' as url_hash, '
                .'SUM('.$this->col('link_click_daily.count').') as total'
            )
            ->pluck('total', 'url_hash');

        $ranked = [];

        foreach ($recent as $row) {
            $count = (int) $row->recent;

            if ($count < $minClicks) {
                continue;
            }

            $priorTotal = (int) ($baseline[$row->url_hash] ?? 0);
            $perDay = $priorTotal / (self::BASELINE_DAYS - 1);

            // Additive smoothing, using the admin's own noise floor as the
            // prior. A hashtag with no history has nothing to divide by, so
            // some constant is needed either way; making it the floor ties
            // "how much evidence is enough" to the number they already set.
            // A constant of 1 is far too weak — it lets a brand-new tag with a
            // handful of clicks outrank one that genuinely tripled.
            $prior = max(1, $minClicks);
            $velocity = ($count + $prior) / ($perDay + $prior);

            $ranked[] = [
                'id' => (int) $row->id,
                'count' => $count,
                'velocity' => round($velocity, 1),
            ];
        }

        usort($ranked, fn (array $a, array $b) => $b['velocity'] <=> $a['velocity']);
        $ranked = array_slice($ranked, 0, self::LIMIT);

        if ($ranked === []) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, PostLink> $links */
        $links = PostLink::query()->whereIn('id', array_column($ranked, 'id'))->get()->keyBy('id');
        $out = [];

        foreach ($ranked as $row) {
            $link = $links->get($row['id']);

            if ($link === null) {
                continue;
            }

            $out[] = [
                'id' => $row['id'],
                'label' => $link->label ?? $link->url,
                'url' => $link->url,
                'count' => $row['count'],
                'velocity' => $row['velocity'],
            ];
        }

        return $out;
    }

    private function col(string $tableDotColumn): string
    {
        return $this->db->getQueryGrammar()->wrap($tableDotColumn);
    }
}
