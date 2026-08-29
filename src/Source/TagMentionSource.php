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
use Datlechin\LinkClicks\Service\TagOptOut;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use s9e\TextFormatter\Utils;

/**
 * `#hashtag` mentions, the `TAGMENTION` tag flarum/mentions produces when
 * flarum/tags is enabled.
 *
 * These are links like any other — they point at `/t/{slug}` — but they came
 * from a different formatter tag, so the extension never saw them. That made
 * click counts quietly inconsistent: pasting a link to a tag page was counted
 * while writing `#tag`, which lands in the same place, was not.
 *
 * Identity is the tag's id, never its slug. flarum/mentions re-resolves the
 * slug on every render, so a renamed tag changes the URL of every mention
 * already written; keying on the URL would split one tag's history in two at
 * each rename.
 */
class TagMentionSource implements TrackableSource
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $urls,
        protected TagOptOut $tagOptOut,
    ) {
    }

    public function key(): string
    {
        return PostLink::SOURCE_TAG_MENTION;
    }

    public function tagName(): string
    {
        return 'TAGMENTION';
    }

    public function extract(string $parsedXml): array
    {
        if (! str_contains($parsedXml, '<TAGMENTION')) {
            return [];
        }

        $ids = array_unique(array_map(
            intval(...),
            Utils::getAttributeValues($parsedXml, 'TAGMENTION', 'id'),
        ));

        if ($ids === []) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Tag> $tags */
        $tags = Tag::query()->whereIn('id', $ids)->get();
        $targets = [];

        foreach ($tags as $tag) {
            // A tag the admin excluded from tracking shouldn't be counted just
            // because someone mentioned it somewhere else. This is a different
            // question from the discussion-level opt-out the listener applies:
            // there, the tags on the discussion decide; here, the mentioned tag
            // does.
            if ($this->tagOptOut->isTagDisabled((int) $tag->id)) {
                continue;
            }

            $hash = $this->hashFor((int) $tag->id);

            $targets[$hash] = new TrackedTarget(
                hash: $hash,
                url: $this->urlFor($tag),
                label: '#'.$tag->name,
                isInternal: true,
                sourceId: (int) $tag->id,
            );
        }

        return $targets;
    }

    public function shouldPersist(TrackedTarget $target): bool
    {
        // Deliberately not gated on track_internal. That setting exists to keep
        // the focus on outbound traffic, and it defaults to off — applying it
        // here would mean no forum ever records a hashtag click unless the
        // admin first turned on something unrelated.
        return (bool) $this->settings->get('datlechin-link-clicks.track_tag_mentions', true);
    }

    public function forwardedAttributes(): array
    {
        // No `class`, `style`, `target` or `rel`: this template computes its
        // own class and style with <xsl:attribute>, and an xsl:copy-of for the
        // same name would fight them. libxslt resolves such a clash by keeping
        // whichever came first rather than last, which would drop
        // TagMention--colored from every hashtag on the forum.
        return ['data-clicks', 'data-post-id', 'data-url-id'];
    }

    public function identify(array $attrs): ?string
    {
        // Rendered by flarum/mentions as an inert span with no link once the
        // tag is gone, so there is nothing to attach tracking to.
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

        $tag = Tag::query()->find($link->source_id);

        // The tag was deleted after the click was rendered. Nowhere sensible
        // to send the reader, so the controller 404s rather than guessing.
        return $tag === null ? null : $this->urlFor($tag);
    }

    private function hashFor(int $tagId): string
    {
        return hash('sha256', PostLink::SOURCE_TAG_MENTION.':'.$tagId);
    }

    private function urlFor(Tag $tag): string
    {
        return $this->urls->to('forum')->route('tag', ['slug' => $tag->slug]);
    }
}
