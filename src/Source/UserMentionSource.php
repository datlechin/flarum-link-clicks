<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Source;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use s9e\TextFormatter\Utils;

/**
 * `@username` mentions, the `USERMENTION` tag from flarum/mentions.
 *
 * Same reasoning as {@see TagMentionSource}: a link to a profile that the
 * extension couldn't see. Keyed on the user's id, because a username change
 * rewrites the link on every existing mention.
 */
class UserMentionSource implements TrackableSource
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $urls,
        protected SlugManager $slugs,
    ) {
    }

    public function key(): string
    {
        return PostLink::SOURCE_USER_MENTION;
    }

    public function tagName(): string
    {
        return 'USERMENTION';
    }

    public function extract(string $parsedXml): array
    {
        if (! str_contains($parsedXml, '<USERMENTION')) {
            return [];
        }

        $ids = array_unique(array_map(
            intval(...),
            Utils::getAttributeValues($parsedXml, 'USERMENTION', 'id'),
        ));

        if ($ids === []) {
            return [];
        }

        $targets = [];

        /** @var User $user */
        foreach (User::query()->whereIn('id', $ids)->get() as $user) {
            $hash = $this->hashFor((int) $user->id);

            $targets[$hash] = new TrackedTarget(
                hash: $hash,
                url: $this->urlFor($user),
                label: '@'.$user->display_name,
                isInternal: true,
                sourceId: (int) $user->id,
            );
        }

        return $targets;
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        return (bool) $this->settings->get('datlechin-link-clicks.track_user_mentions', true);
    }

    public function forwardedAttributes(): array
    {
        // This template carries a literal class="UserMention", so `class` is
        // off the list for the same reason as in TagMentionSource.
        return ['data-clicks', 'data-post-id', 'data-url-id'];
    }

    public function identify(array $attrs): ?string
    {
        if (($attrs['deleted'] ?? '0') === '1') {
            return null;
        }

        $id = (int) ($attrs['id'] ?? 0);

        return $id > 0 ? $this->hashFor($id) : null;
    }

    public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
    {
        $attrs['data-lc-href'] = $trackingUrl;
        $attrs['data-post-id'] = (string) $context->post->id;
        $attrs['data-url-id'] = (string) $link->id;

        if ($link->clicks_count >= $context->minDisplayCount) {
            $attrs['data-clicks'] = (string) $link->clicks_count;
        }

        return $attrs;
    }

    public function resolveTarget(PostLink $link): ?string
    {
        if ($link->source_id === null) {
            return null;
        }

        $user = User::query()->find($link->source_id);

        return $user === null ? null : $this->urlFor($user);
    }

    private function hashFor(int $userId): string
    {
        return hash('sha256', PostLink::SOURCE_USER_MENTION.':'.$userId);
    }

    private function urlFor(User $user): string
    {
        return $this->urls->to('forum')->route('user', [
            'username' => $this->slugs->forResource(User::class)->toSlug($user),
        ]);
    }
}
