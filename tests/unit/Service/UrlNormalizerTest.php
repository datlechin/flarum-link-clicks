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

use Datlechin\LinkClicks\Service\UrlNormalizer;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class UrlNormalizerTest extends TestCase
{
    private UrlNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('datlechin-link-clicks.tracking_params_strip', '')->andReturn('');
        $settings->shouldReceive('get')->with('datlechin-link-clicks.attachment_path_prefixes', '')->andReturn('');
        $settings->shouldReceive('get')->with('datlechin-link-clicks.domain_blocklist', '')->andReturn('');

        $config = new Config(['url' => 'https://flarum.test']);

        $this->normalizer = new UrlNormalizer($settings, $config);
    }

    #[Test]
    public function it_returns_null_for_non_http_schemes(): void
    {
        $this->assertNull($this->normalizer->normalize('ftp://example.com/file'));
        $this->assertNull($this->normalizer->normalize('javascript:alert(1)'));
        $this->assertNull($this->normalizer->normalize('mailto:foo@bar.com'));
        $this->assertNull($this->normalizer->normalize('not-a-url'));
        $this->assertNull($this->normalizer->normalize(''));
    }

    #[Test]
    public function it_returns_null_for_urls_without_a_host(): void
    {
        $this->assertNull($this->normalizer->normalize('http://'));
        $this->assertNull($this->normalizer->normalize('https:///path'));
    }

    #[Test]
    public function it_lowercases_scheme_and_host(): void
    {
        $result = $this->normalizer->normalize('HTTPS://EXAMPLE.COM/Path');
        $this->assertNotNull($result);
        $this->assertSame('https://example.com/Path', $result->value);
        $this->assertSame('example.com', $result->host);
    }

    #[Test]
    public function it_strips_default_ports(): void
    {
        $this->assertSame('http://example.com/x', $this->normalizer->normalize('http://example.com:80/x')?->value);
        $this->assertSame('https://example.com/x', $this->normalizer->normalize('https://example.com:443/x')?->value);
    }

    #[Test]
    public function it_preserves_non_default_ports(): void
    {
        $this->assertSame('https://example.com:8443/x', $this->normalizer->normalize('https://example.com:8443/x')?->value);
    }

    #[Test]
    public function it_strips_trailing_slash_from_path(): void
    {
        $this->assertSame('https://example.com/page', $this->normalizer->normalize('https://example.com/page/')?->value);
        $this->assertSame('https://example.com', $this->normalizer->normalize('https://example.com/')?->value);
        $this->assertSame('https://example.com', $this->normalizer->normalize('https://example.com')?->value);
    }

    #[Test]
    public function it_strips_tracking_query_params(): void
    {
        $result = $this->normalizer->normalize('https://example.com/page?utm_source=newsletter&utm_medium=email&fbclid=xyz&keep=me');
        $this->assertSame('https://example.com/page?keep=me', $result?->value);
    }

    #[Test]
    public function utm_wildcard_strips_arbitrary_utm_keys(): void
    {
        $result = $this->normalizer->normalize('https://example.com/?utm_id=42&utm_brand=acme&keep=ok');
        $this->assertSame('https://example.com?keep=ok', $result?->value);
    }

    #[Test]
    public function igshid_and_msclkid_are_stripped_by_default(): void
    {
        $result = $this->normalizer->normalize('https://example.com/p?igshid=ig123&msclkid=ms456&x=1');
        $this->assertSame('https://example.com/p?x=1', $result?->value);
    }

    #[Test]
    public function admin_supplied_strip_pattern_is_applied(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')
            ->with('datlechin-link-clicks.tracking_params_strip', '')
            ->andReturn("ref_url\ncustom_*");
        $settings->shouldReceive('get')
            ->with('datlechin-link-clicks.attachment_path_prefixes', '')
            ->andReturn('');

        $config = new Config(['url' => 'https://flarum.test']);
        $normalizer = new UrlNormalizer($settings, $config);

        $result = $normalizer->normalize('https://example.com/?ref_url=email&custom_x=1&custom_y=2&keep=ok');
        $this->assertSame('https://example.com?keep=ok', $result?->value);
    }

    #[Test]
    public function discussion_url_drops_slug_for_canonical_hash(): void
    {
        $a = $this->normalizer->normalize('https://flarum.test/d/42-old-title');
        $b = $this->normalizer->normalize('https://flarum.test/d/42-new-title');
        $c = $this->normalizer->normalize('https://flarum.test/d/42');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertNotNull($c);
        $this->assertSame($a->hash, $b->hash);
        $this->assertSame($a->hash, $c->hash);
    }

    #[Test]
    public function discussion_url_preserves_near_anchor(): void
    {
        $a = $this->normalizer->normalize('https://flarum.test/d/42-old/5');
        $b = $this->normalizer->normalize('https://flarum.test/d/42/5');
        $c = $this->normalizer->normalize('https://flarum.test/d/42-old/3');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertNotNull($c);
        // Same near anchor canonicalises identically...
        $this->assertSame($a->hash, $b->hash);
        // ...but different anchors stay distinct.
        $this->assertNotSame($a->hash, $c->hash);
    }

    #[Test]
    public function slug_canonicalization_only_applies_to_forum_host(): void
    {
        $external = $this->normalizer->normalize('https://other.test/d/42-something');
        $this->assertSame('https://other.test/d/42-something', $external?->value);
    }

    #[Test]
    public function attachment_path_is_detected(): void
    {
        $result = $this->normalizer->normalize('https://flarum.test/assets/files/abc.pdf');
        $this->assertNotNull($result);
        $this->assertTrue($result->isAttachment);
    }

    #[Test]
    public function external_url_is_not_an_attachment(): void
    {
        $result = $this->normalizer->normalize('https://example.com/some.pdf');
        $this->assertNotNull($result);
        $this->assertFalse($result->isAttachment);
    }

    #[Test]
    public function discussion_url_is_not_an_attachment(): void
    {
        $result = $this->normalizer->normalize('https://flarum.test/d/42');
        $this->assertNotNull($result);
        $this->assertFalse($result->isAttachment);
    }

    #[Test]
    public function it_sorts_query_params_alphabetically(): void
    {
        $result = $this->normalizer->normalize('https://example.com/?z=1&a=2&m=3');
        $this->assertSame('https://example.com?a=2&m=3&z=1', $result?->value);
    }

    #[Test]
    public function it_preserves_query_params_with_dots(): void
    {
        // Regression: parse_str() mangles dot-keys to underscores. Manual
        // parsing keeps `v=1.5` intact so the redirect URL matches the original.
        $result = $this->normalizer->normalize('https://example.com/api?v=1.5&user.id=42');
        $this->assertSame('https://example.com/api?user.id=42&v=1.5', $result?->value);
    }

    #[Test]
    public function it_emits_consistent_hash_for_same_canonical_url(): void
    {
        $a = $this->normalizer->normalize('https://Example.COM/x?b=2&a=1');
        $b = $this->normalizer->normalize('https://example.com:443/x/?a=1&b=2&utm_source=x');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertSame($a->hash, $b->hash);
        $this->assertSame(64, strlen($a->hash));
    }

    #[Test]
    public function it_emits_different_hash_for_different_urls(): void
    {
        $a = $this->normalizer->normalize('https://example.com/a');
        $b = $this->normalizer->normalize('https://example.com/b');
        $this->assertNotSame($a?->hash, $b?->hash);
    }

    #[Test]
    #[DataProvider('hostExtractionCases')]
    public function it_extracts_lowercased_host(string $input, string $expectedHost): void
    {
        $this->assertSame($expectedHost, $this->normalizer->normalize($input)?->host);
    }

    public static function hostExtractionCases(): array
    {
        return [
            'plain' => ['https://example.com', 'example.com'],
            'subdomain' => ['https://news.example.com/x', 'news.example.com'],
            'uppercase host' => ['https://EXAMPLE.COM', 'example.com'],
            'with port' => ['https://example.com:8443/x', 'example.com'],
            'punycode' => ['https://xn--80akhbyknj4f/x', 'xn--80akhbyknj4f'],
        ];
    }
}
