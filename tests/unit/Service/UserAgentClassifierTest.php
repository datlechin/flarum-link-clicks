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

use Datlechin\LinkClicks\Service\UserAgentClassifier;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class UserAgentClassifierTest extends TestCase
{
    private UserAgentClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('');
        $this->classifier = new UserAgentClassifier($settings);
    }

    #[Test]
    public function admin_supplied_fragment_is_treated_as_bot(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('wp-fetch, custom-monitor');
        $classifier = new UserAgentClassifier($settings);

        $this->assertSame('bot', $classifier->classify('Mozilla/5.0 wp-fetch/1.0'));
        $this->assertSame('bot', $classifier->classify('Custom-Monitor/2.1'));
    }

    #[Test]
    public function builtin_bot_detection_unaffected_by_custom_list(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('foo,bar');
        $classifier = new UserAgentClassifier($settings);

        $this->assertSame('bot', $classifier->classify('Googlebot/2.1'));
    }

    #[Test]
    #[DataProvider('classificationCases')]
    public function it_classifies_user_agents(string $rawUa, string $expected): void
    {
        $this->assertSame($expected, $this->classifier->classify($rawUa));
    }

    public static function classificationCases(): array
    {
        return [
            'empty string is other' => ['', 'other'],
            'googlebot is bot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'bot'],
            'facebook crawler is bot' => ['facebookexternalhit/1.1', 'bot'],
            'curl is bot' => ['curl/7.79.1', 'bot'],
            'python requests is bot' => ['python-requests/2.28.1', 'bot'],
            'headless chrome is bot' => ['Mozilla/5.0 HeadlessChrome/120.0.6099.71', 'bot'],
            'desktop chrome' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.6099.71 Safari/537.36', 'chrome'],
            'firefox' => ['Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0', 'firefox'],
            'safari real' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'safari'],
            'edge' => ['Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0', 'edge'],
            'unknown ua' => ['SomeRandomClient/1.0', 'other'],
        ];
    }

    #[Test]
    public function bot_check_takes_priority_over_browser_match(): void
    {
        // A UA that contains both 'bot' and 'chrome' must classify as bot.
        // Some scrapers spoof a chrome UA but include "bot" in the product token.
        $this->assertSame('bot', $this->classifier->classify('Mozilla/5.0 ChromeBot/2.0'));
    }

    #[Test]
    #[DataProvider('deviceCases')]
    public function it_classifies_device_class(string $rawUa, string $expected): void
    {
        $this->assertSame($expected, $this->classifier->classifyDevice($rawUa));
    }

    public static function deviceCases(): array
    {
        return [
            'empty defaults to desktop' => ['', 'desktop'],
            'iphone is mobile' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'mobile'],
            'android phone is mobile' => ['Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36', 'mobile'],
            'ipad is tablet' => ['Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'tablet'],
            'android tablet is tablet (no Mobile token)' => ['Mozilla/5.0 (Linux; Android 13; SM-X800) AppleWebKit/537.36 Chrome/120 Safari/537.36', 'tablet'],
            'desktop chrome' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.6099.71 Safari/537.36', 'desktop'],
            'windows firefox' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0', 'desktop'],
            'unknown ua defaults to desktop' => ['SomeBox/1.0', 'desktop'],
        ];
    }

    #[Test]
    #[DataProvider('browserCases')]
    public function it_classifies_browser_family(string $rawUa, string $expected): void
    {
        $this->assertSame($expected, $this->classifier->classifyBrowser($rawUa));
    }

    public static function browserCases(): array
    {
        return [
            'empty is other' => ['', 'other'],
            'edge wins over chrome+safari mention' => ['Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0', 'edge'],
            'opera' => ['Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/120 Safari/537.36 OPR/106.0', 'opera'],
            'samsung internet' => ['Mozilla/5.0 (Linux; Android 13) SamsungBrowser/24.0 Chrome/115 Mobile Safari/537.36', 'samsung'],
            'firefox desktop' => ['Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0', 'firefox'],
            'firefox ios (FxiOS)' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 FxiOS/121.0', 'firefox'],
            'chrome ios (CriOS)' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 CriOS/120.0', 'chrome'],
            'chrome desktop' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36', 'chrome'],
            'safari real' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'safari'],
            'unknown' => ['SomeBox/1.0', 'other'],
        ];
    }
}
