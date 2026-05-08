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

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Per-URL drill-down: lists every actor (logged-in user or anonymous IP)
 * that produced a counted click on any post_link sharing the given
 * url_hash, with their per-actor total + first/last click timestamps.
 *
 * Aggregates across post_links because the analytics table groups by URL,
 * not by post_link_id, so drilling into a row should follow that grouping.
 *
 * Logged-in users are grouped by user_id; guest clicks are grouped by
 * ip_address. Anonymized rows (user_id and ip_address both null after a
 * GDPR erasure) are collapsed into a single synthetic "anonymized" group
 * so admins can see how many clicks were lost without exposing PII.
 */
class ListLinkClickersController implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 25;
    private const MAX_LIMIT = 100;

    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('datlechin-link-clicks.viewAnalytics');

        $params = $request->getQueryParams();
        $urlHash = (string) Arr::get($params, 'id');
        if ($urlHash === '' || ! preg_match('/^[a-f0-9]{64}$/', $urlHash)) {
            return new JsonResponse(['error' => 'Invalid url_hash.'], 422);
        }

        $offset = max(0, (int) Arr::get($params, 'page.offset', 0));
        $limit = min(self::MAX_LIMIT, max(1, (int) Arr::get($params, 'page.limit', self::DEFAULT_LIMIT)));

        $linkIds = $this->db->table('post_links')
            ->join('posts', 'posts.id', '=', 'post_links.post_id')
            ->where('post_links.url_hash', $urlHash)
            ->where('posts.link_clicks_disabled', false)
            ->pluck('post_links.id');

        if ($linkIds->isEmpty()) {
            return new JsonResponse(['rows' => [], 'total' => 0, 'offset' => $offset, 'limit' => $limit]);
        }

        $base = $this->db->table('link_click_events')
            ->whereIn('post_link_id', $linkIds)
            ->where('counted', true);

        $totalGroups = (clone $base)
            ->selectRaw('COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), ip_address, "__anon__")) as c')
            ->value('c');

        $rows = (clone $base)
            ->selectRaw('user_id, ip_address,
                         COUNT(*) as click_count,
                         MIN(clicked_at) as first_click,
                         MAX(clicked_at) as last_click')
            ->groupBy('user_id', 'ip_address')
            ->orderBy('click_count', 'desc')
            ->orderBy('last_click', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $userIds = $rows->pluck('user_id')->filter()->unique()->values()->all();
        $users = $userIds === []
            ? collect()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $data = $rows->map(function (object $row) use ($users) {
            $user = $row->user_id ? $users->get((int) $row->user_id) : null;

            return [
                'user' => $user instanceof User ? [
                    'id' => (int) $user->id,
                    'username' => $user->username,
                    'displayName' => $user->display_name,
                    'avatarUrl' => $user->avatar_url,
                ] : null,
                'ip_address' => $row->user_id === null ? $row->ip_address : null,
                'anonymized' => $row->user_id === null && $row->ip_address === null,
                'click_count' => (int) $row->click_count,
                'first_click' => $row->first_click,
                'last_click' => $row->last_click,
            ];
        })->all();

        return new JsonResponse([
            'rows' => $data,
            'total' => (int) $totalGroups,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }
}
