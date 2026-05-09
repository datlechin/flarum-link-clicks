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
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Synchronous test ping for the webhook tab. Builds the same payload shape
 * a real click event uses (with `event: "test_ping"`) and posts it once,
 * surfacing the receiver's status code and response excerpt back to the
 * admin. No queue, no retries: the admin clicked a button and wants an
 * answer now.
 */
class TestWebhookController implements RequestHandlerInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Client $http,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $url = trim((string) $this->settings->get('datlechin-link-clicks.webhook_url', ''));
        if ($url === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Webhook URL is not configured.'], 422);
        }

        $secret = (string) $this->settings->get('datlechin-link-clicks.webhook_secret', '');
        $payload = [
            'event' => 'test_ping',
            'sent_at' => Carbon::now()->toIso8601String(),
            'note' => 'This is a manual test ping from Admin → Link Clicks → Webhook.',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'datlechin-flarum-link-clicks',
            'X-LinkClicks-Delivery-Id' => 'test-'.bin2hex(random_bytes(8)),
        ];
        if ($secret !== '') {
            $headers['X-LinkClicks-Signature'] = 'sha256='.hash_hmac('sha256', $body, $secret);
        }

        try {
            $response = $this->http->post($url, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 5,
                'connect_timeout' => 3,
                'http_errors' => false,
            ]);
        } catch (TransferException $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 200);
        }

        $status = $response->getStatusCode();
        $bodyText = (string) $response->getBody();
        $excerpt = mb_strlen($bodyText) > 500 ? mb_substr($bodyText, 0, 500).'…' : $bodyText;

        return new JsonResponse([
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $excerpt,
        ]);
    }
}
