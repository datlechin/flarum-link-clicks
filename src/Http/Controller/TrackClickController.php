<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Http\Controller;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\ClickRecorder;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Datlechin\LinkClicks\ValueObject\ClickContext;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /lcc/track?u=<token>`. Verifies the HMAC-signed token, records the
 * click, atomically increments the per-link counter when the click qualifies,
 * and 302s to the original URL.
 *
 * Returns 404 on every failure path without distinguishing which check failed,
 * so we never reveal whether a post or link exists.
 */
class TrackClickController implements RequestHandlerInterface
{
    public function __construct(
        protected TrackingUrlSigner $signer,
        protected ClickRecorder $recorder,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $token = (string) ($request->getQueryParams()['u'] ?? '');
        if ($token === '') {
            return new EmptyResponse(404);
        }

        $payload = $this->signer->verify($token);
        if ($payload === null) {
            return new EmptyResponse(404);
        }

        /** @var PostLink|null $postLink */
        $postLink = PostLink::query()->with('post')->find($payload->postLinkId);
        if ($postLink === null || $postLink->post === null) {
            return new EmptyResponse(404);
        }

        $actor = RequestUtil::getActor($request);

        // `isVisibleTo` applies the canonical `ScopeVisibilityTrait` filter
        // (tag permissions, hidden flag, soft-deletes). The `view` ability on
        // PostPolicy checks `viewPosts`, a permission rarely granted, which
        // would 404 most legitimate users.
        if (! $postLink->post->isVisibleTo($actor)) {
            return new EmptyResponse(404);
        }

        // Defence-in-depth: validate the redirect destination scheme. The URL
        // is normalised to http(s) at write time, but re-checking here closes
        // the door to any future code path that bypasses the normaliser.
        $scheme = parse_url($postLink->url, PHP_URL_SCHEME);
        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], strict: true)) {
            return new EmptyResponse(404);
        }

        // Per-post opt-out: skip recording entirely but still redirect so
        // stale HTML in someone else's open tab doesn't 404 after the author
        // toggled tracking off.
        if (! (bool) ($postLink->post->link_clicks_disabled ?? false)) {
            $context = new ClickContext(
                actor: $actor,
                ipAddress: (string) $request->getAttribute('ipAddress', ''),
                userAgentRaw: $request->getHeaderLine('User-Agent'),
                dnt: $request->getHeaderLine('DNT') === '1',
                postLink: $postLink,
                now: Carbon::now(),
            );

            $this->recorder->record($context);
        }

        return (new RedirectResponse($postLink->url))
            ->withHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            ->withHeader('Cache-Control', 'no-store');
    }
}
