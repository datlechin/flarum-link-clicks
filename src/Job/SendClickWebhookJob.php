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
use Throwable;

/**
 * Posts a click event to the configured webhook URL. When a secret is set,
 * signs the body with HMAC-SHA256 using a GitHub-style header
 * (`X-LinkClicks-Signature: sha256=<hex>`).
 *
 * Each delivery attempt carries a stable `X-LinkClicks-Delivery-Id` UUID so
 * receivers can dedupe retries. Retries follow an exponential backoff
 * (10s, 60s, 5m, 30m, 2h); after `$tries` attempts Laravel routes the job
 * to `failed_jobs` and we log a final warning so admins can spot dead
 * webhooks without trawling queue logs.
 */
class SendClickWebhookJob extends AbstractJob
{
    public int $tries = 5;
    public int $timeout = 30;

    public readonly string $deliveryId;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $url,
        public readonly string $secret,
        public readonly array $payload,
        ?string $deliveryId = null,
    ) {
        $this->deliveryId = $deliveryId ?? self::uuidv4();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 1800, 7200];
    }

    public function handle(Client $http, LoggerInterface $logger): void
    {
        $body = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'datlechin-flarum-link-clicks',
            'X-LinkClicks-Delivery-Id' => $this->deliveryId,
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
                'delivery_id' => $this->deliveryId,
                'event' => $this->payload['event'] ?? null,
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        // Last-resort log when all retries are exhausted. The job has
        // already been pushed to failed_jobs by the queue worker; this
        // record gives admins something to grep without digging into queue
        // tables.
        if (! function_exists('resolve')) {
            return;
        }

        try {
            /** @var LoggerInterface $logger */
            $logger = resolve(LoggerInterface::class);
            $logger->error('Link clicks webhook gave up after '.$this->tries.' attempts: '.$exception->getMessage(), [
                'url' => $this->url,
                'delivery_id' => $this->deliveryId,
                'event' => $this->payload['event'] ?? null,
            ]);
        } catch (Throwable) {
            // Fall through; we're already in a failure path.
        }
    }

    private static function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
