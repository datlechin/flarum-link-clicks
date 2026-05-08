<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Listener;

use Datlechin\LinkClicks\Event\ClickRecorded;
use Datlechin\LinkClicks\Job\SendClickWebhookJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;

/**
 * Bridges `ClickRecorded` to the webhook job. No-op when `webhook_url` is
 * unset so registration is unconditional and toggling is a settings change
 * without needing an extension reload.
 */
class DispatchClickWebhook
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue,
    ) {
    }

    public function handle(ClickRecorded $event): void
    {
        if (! (bool) $this->settings->get('datlechin-link-clicks.webhook_enabled', false)) {
            return;
        }

        $url = trim((string) $this->settings->get('datlechin-link-clicks.webhook_url', ''));
        if ($url === '') {
            return;
        }

        $secret = (string) $this->settings->get('datlechin-link-clicks.webhook_secret', '');
        $context = $event->context;
        $link = $context->postLink;

        $payload = [
            'event' => 'click_recorded',
            'counted' => $event->counted,
            'post_link' => [
                'id' => (int) $link->id,
                'url' => $link->url,
                'is_internal' => (bool) $link->is_internal,
                'is_attachment' => (bool) $link->is_attachment,
                'post_id' => (int) $link->post_id,
                'discussion_id' => (int) $link->discussion_id,
                'clicks_count' => (int) $link->clicks_count,
            ],
            'actor' => $context->actor->isGuest() ? null : [
                'user_id' => (int) $context->actor->id,
                'username' => $context->actor->username,
            ],
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgentRaw,
            'clicked_at' => $context->now->toIso8601String(),
        ];

        $this->queue->push(new SendClickWebhookJob($url, $secret, $payload));
    }
}
