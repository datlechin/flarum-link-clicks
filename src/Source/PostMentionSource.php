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
use Flarum\Http\UrlGenerator;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use s9e\TextFormatter\Utils;

/**
 * Post mentions, the `POSTMENTION` tag from flarum/mentions — the link a quote
 * or an `@"Name"#p123` reply leaves behind.
 *
 * Keyed on the mentioned post's id. The rendered URL includes the discussion's
 * slug and the post's number, both of which can move underneath the mention.
 */
class PostMentionSource implements TrackableSource
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $urls,
    ) {
    }

    public function key(): string
    {
        return PostLink::SOURCE_POST_MENTION;
    }

    public function tagName(): string
    {
        return 'POSTMENTION';
    }

    public function extract(string $parsedXml): array
    {
        if (! str_contains($parsedXml, '<POSTMENTION')) {
            return [];
        }

        $ids = array_unique(array_map(
            intval(...),
            Utils::getAttributeValues($parsedXml, 'POSTMENTION', 'id'),
        ));

        if ($ids === []) {
            return [];
        }

        $targets = [];

        /** @var Post $post */
        foreach (Post::query()->whereIn('id', $ids)->get() as $post) {
            $hash = $this->hashFor((int) $post->id);

            $targets[$hash] = new TrackedTarget(
                hash: $hash,
                url: $this->urlFor($post),
                label: '#'.$post->id,
                isInternal: true,
                sourceId: (int) $post->id,
            );
        }

        return $targets;
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        return (bool) $this->settings->get('datlechin-link-clicks.track_post_mentions', true);
    }

    public function forwardedAttributes(): array
    {
        // Literal class="PostMention" in the template; see TagMentionSource.
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

        $post = Post::query()->find($link->source_id);

        return $post === null ? null : $this->urlFor($post);
    }

    private function hashFor(int $postId): string
    {
        return hash('sha256', PostLink::SOURCE_POST_MENTION.':'.$postId);
    }

    private function urlFor(Post $post): string
    {
        return $this->urls->to('forum')->route('discussion', [
            'id' => $post->discussion_id,
            'near' => $post->number,
        ]);
    }
}
