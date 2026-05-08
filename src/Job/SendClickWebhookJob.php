<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Job;

use Flarum\Queue\AbstractJob;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

/**
 * Posts a click event to the configured webhook URL. When a secret is set,
 * signs the request body with HMAC-SHA256 using a GitHub-style header
 * (`X-LinkClicks-Signature: sha256=<hex>`) so receivers can verify origin.
 *
 * Failures are logged but do not retry forever; queue worker config decides
 * the backoff. We want webhook delivery to be best-effort, never block the
 * click redirect path.
 */
class SendClickWebhookJob extends AbstractJob
{
    public int $tries = 3;
    public int $timeout = 10;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $url,
        public readonly string $secret,
        public readonly array $payload,
    ) {
    }

    public function handle(Client $http, LoggerInterface $logger): void
    {
        $body = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'datlechin-flarum-link-clicks',
        ];

        if ($this->secret !== '') {
            $headers['X-LinkClicks-Signature'] = 'sha256='.hash_hmac('sha256', $body, $this->secret);
        }

        try {
            $http->post($this->url, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 5,
                'connect_timeout' => 3,
                'http_errors' => true,
            ]);
        } catch (TransferException $e) {
            $logger->warning('Link clicks webhook delivery failed: '.$e->getMessage(), [
                'url' => $this->url,
                'event' => $this->payload['event'] ?? null,
            ]);

            throw $e;
        }
    }
}
