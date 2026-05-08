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
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $post_link_id
 * @property int|null    $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool        $counted
 * @property Carbon      $clicked_at
 * @property-read PostLink $postLink
 * @property-read User|null $user
 */
class LinkClickEvent extends AbstractModel
{
    protected $table = 'link_click_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'counted' => 'boolean',
        'clicked_at' => 'datetime',
    ];

    public function postLink(): BelongsTo
    {
        return $this->belongsTo(PostLink::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
