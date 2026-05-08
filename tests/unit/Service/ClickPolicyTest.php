<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Unit\Service;

use Carbon\Carbon;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\ClickPolicy;
use Datlechin\LinkClicks\Service\UserAgentClassifier;
use Datlechin\LinkClicks\ValueObject\ClickContext;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class ClickPolicyTest extends TestCase
{
    private function policy(SettingsRepositoryInterface $settings, string $uaFamily = 'chrome'): ClickPolicy
    {
        $classifier = Mockery::mock(UserAgentClassifier::class);
        $classifier->shouldReceive('classify')->andReturn($uaFamily);

        return new ClickPolicy($settings, $classifier);
    }

    private function context(User $actor, bool $dnt = false): ClickContext
    {
        return new ClickContext(
            actor: $actor,
            ipAddress: '1.2.3.4',
            userAgentRaw: 'Mozilla',
            dnt: $dnt,
            postLink: new PostLink(),
            now: Carbon::now(),
        );
    }

    #[Test]
    public function bot_user_agents_are_dropped(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(true);

        $policy = $this->policy($settings, uaFamily: 'bot');

        $this->assertFalse($policy->shouldRecord($this->context($actor)));
    }

    #[Test]
    public function dnt_drops_when_honor_dnt_is_on(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.honor_dnt', true)->andReturn(true);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(true);

        $policy = $this->policy($settings);

        $this->assertFalse($policy->shouldRecord($this->context($actor, dnt: true)));
    }

    #[Test]
    public function skip_guests_setting_drops_anonymous_clicks(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.honor_dnt', true)->andReturn(true);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.skip_guests', false)->andReturn(true);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(true);

        $policy = $this->policy($settings);

        $this->assertFalse($policy->shouldRecord($this->context($actor)));
    }

    #[Test]
    public function skip_guests_does_not_affect_logged_in_users(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.honor_dnt', true)->andReturn(true);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.skip_guests', false)->andReturn(true);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getPreference')->with('datlechin-link-clicks.opt_out', false)->andReturn(false);

        $policy = $this->policy($settings);

        $this->assertTrue($policy->shouldRecord($this->context($actor)));
    }

    #[Test]
    public function user_opt_out_preference_drops_their_clicks(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.honor_dnt', true)->andReturn(true);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.skip_guests', false)->andReturn(false);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getPreference')->with('datlechin-link-clicks.opt_out', false)->andReturn(true);

        $policy = $this->policy($settings);

        $this->assertFalse($policy->shouldRecord($this->context($actor)));
    }

    #[Test]
    public function default_path_records_when_nothing_blocks(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->withAnyArgs()->andReturn(false);

        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getPreference')->with('datlechin-link-clicks.opt_out', false)->andReturn(false);

        $policy = $this->policy($settings);

        $this->assertTrue($policy->shouldRecord($this->context($actor)));
    }
}
