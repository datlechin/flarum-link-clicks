<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Console;

use Illuminate\Console\Scheduling\Event;

class DailySchedule
{
    public function __invoke(Event $event): void
    {
        $event->daily()->withoutOverlapping();
    }
}
