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

use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ListPopularLinksController implements RequestHandlerInterface
{
    private const LIMIT = 5;
    private const TTL_SECONDS = 60;

    public function __construct(
        protected CacheRepository $cache,
        protected SettingsRepositoryInterface $settings,
        protected TrackingUrlSigner $signer,
        protected UrlGenerator $urls,
        protected ConnectionInterface $db,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $discussionId = (int) Arr::get($request->getQueryParams(), 'id');

        $discussion = Discussion::query()->whereVisibleTo($actor)->find($discussionId);
        if ($discussion === null) {
            return new EmptyResponse(404);
        }

        $minDisplay = (int) $this->settings->get('datlechin-link-clicks.min_display_count', 1);

        $rows = $this->cache->remember(
            "datlechin-link-clicks.popular.{$discussionId}.{$minDisplay}",
            self::TTL_SECONDS,
            fn () => $this->db->table('post_links')
                ->join('posts', 'posts.id', '=', 'post_links.post_id')
                ->where('post_links.discussion_id', $discussionId)
                ->where('post_links.is_internal', false)
                ->where('posts.link_clicks_disabled', false)
                ->groupBy('post_links.url_hash')
                ->selectRaw('post_links.url_hash,
                             MIN(post_links.id) as id,
                             MIN(post_links.url) as url,
                             SUM(post_links.clicks_count) as clicks_count,
                             MAX(post_links.last_clicked_at) as last_clicked_at')
                ->havingRaw('SUM(post_links.clicks_count) >= ?', [$minDisplay])
                ->orderByDesc('clicks_count')
                ->orderByDesc('last_clicked_at')
                ->limit(self::LIMIT)
                ->get(),
        );

        $trackBase = $this->urls->to('forum')->route('datlechin-link-clicks.track');

        $data = $rows->map(fn (object $row) => [
            'id' => (int) $row->id,
            'track_url' => $trackBase.'?u='.$this->signer->sign((int) $row->id),
            'display' => PostLink::truncateForDisplay($row->url),
            'title' => $row->url,
            'count' => (int) $row->clicks_count,
        ])->all();

        return new JsonResponse($data);
    }
}
