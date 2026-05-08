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

use Datlechin\LinkClicks\LinkClickEvent;
use Datlechin\LinkClicks\ValueObject\ClickContext;
use Flarum\Settings\SettingsRepositoryInterface;

class ClickPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UserAgentClassifier $uaClassifier,
    ) {
    }

    public function shouldRecord(ClickContext $ctx): bool
    {
        if ($this->uaClassifier->classify($ctx->userAgentRaw) === 'bot') {
            return false;
        }

        if ($ctx->dnt && (bool) $this->settings->get('datlechin-link-clicks.honor_dnt', true)) {
            return false;
        }

        if ($ctx->actor->isGuest()) {
            if ((bool) $this->settings->get('datlechin-link-clicks.skip_guests', false)) {
                return false;
            }
        } elseif ((bool) $ctx->actor->getPreference('datlechin-link-clicks.opt_out', false)) {
            // User opted out via their account settings. We still serve the
            // redirect (the click works) but no row is written.
            return false;
        }

        return true;
    }

    public function isCounted(ClickContext $ctx): bool
    {
        return ! $this->isAuthor($ctx) && ! $this->isDuplicate($ctx);
    }

    private function isAuthor(ClickContext $ctx): bool
    {
        if ($ctx->actor->isGuest() || $ctx->postLink->post === null) {
            return false;
        }

        return $ctx->actor->id === $ctx->postLink->post->user_id;
    }

    private function isDuplicate(ClickContext $ctx): bool
    {
        $windowHours = (int) $this->settings->get('datlechin-link-clicks.dedup_window_hours', 24);
        $since = $ctx->now->copy()->subHours($windowHours);

        $query = LinkClickEvent::query()
            ->where('post_link_id', $ctx->postLink->id)
            ->where('counted', true)
            ->where('clicked_at', '>=', $since);

        if ($ctx->actor->isGuest()) {
            return $query->whereNull('user_id')
                ->where('ip_address', $ctx->ipAddress)
                ->exists();
        }

        return $query->where('user_id', $ctx->actor->id)->exists();
    }
}
