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
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reverse drill-down: given a user, return the links they clicked, most
 * recent first. The drill-down modal pivots into this when an admin clicks
 * a username from the per-link clickers list.
 */
class ListUserClickTrailController implements RequestHandlerInterface
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

        $userId = (int) Arr::get($request->getQueryParams(), 'id');
        if ($userId <= 0) {
            return new EmptyResponse(404);
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return new EmptyResponse(404);
        }

        $offset = max(0, (int) Arr::get($request->getQueryParams(), 'page.offset', 0));
        $limit = min(self::MAX_LIMIT, max(1, (int) Arr::get($request->getQueryParams(), 'page.limit', self::DEFAULT_LIMIT)));

        $base = $this->db->table('link_click_events')
            ->join('post_links', 'post_links.id', '=', 'link_click_events.post_link_id')
            ->where('link_click_events.user_id', $userId)
            ->where('link_click_events.counted', true);

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->select([
                'link_click_events.clicked_at',
                'post_links.id as post_link_id',
                'post_links.url',
                'post_links.discussion_id',
                'post_links.post_id',
                'post_links.is_internal',
                'post_links.is_attachment',
            ])
            ->orderByDesc('link_click_events.clicked_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn (object $row) => [
            'url' => $row->url,
            'discussion_id' => (int) $row->discussion_id,
            'post_id' => (int) $row->post_id,
            'is_internal' => (bool) $row->is_internal,
            'is_attachment' => (bool) $row->is_attachment,
            'clicked_at' => $row->clicked_at,
        ])->all();

        return new JsonResponse([
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'displayName' => $user->display_name,
                'avatarUrl' => $user->avatar_url,
            ],
            'rows' => $data,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }
}
