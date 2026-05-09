<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $post_id
 * @property int         $discussion_id
 * @property string      $url
 * @property string      $url_hash
 * @property bool        $is_internal
 * @property bool        $is_attachment
 * @property string|null $domain
 * @property int         $clicks_count
 * @property Carbon      $first_seen_at
 * @property Carbon|null $last_clicked_at
 * @property-read Post   $post
 * @property string      $display_url
 */
class PostLink extends AbstractModel
{
    protected $table = 'post_links';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_attachment' => 'boolean',
        'clicks_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LinkClickEvent::class);
    }

    public function getDisplayUrlAttribute(): string
    {
        return self::truncateForDisplay($this->url);
    }

    /**
     * Format a raw URL for compact display (host + at most 1-2 path segments,
     * ellipsised when longer than 30 chars). Static so aggregation queries
     * that don't hydrate `PostLink` models can still produce the same output.
     */
    public static function truncateForDisplay(string $url): string
    {
        $stripped = preg_replace('#^https?://#i', '', $url) ?? $url;

        if (mb_strlen($stripped) <= 30) {
            return $stripped;
        }

        $parts = explode('/', $stripped, 4);
        $head = count($parts) > 2 ? $parts[0].'/'.$parts[1].'/...' : $stripped;

        if (mb_strlen($head) <= 30) {
            return $head;
        }

        return mb_substr($stripped, 0, 27).'...';
    }
}
