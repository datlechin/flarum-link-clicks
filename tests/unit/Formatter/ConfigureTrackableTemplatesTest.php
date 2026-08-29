<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\unit\Formatter;

use Datlechin\LinkClicks\Contract\TrackableSource;
use Datlechin\LinkClicks\Formatter\ConfigureTrackableTemplates;
use Datlechin\LinkClicks\PostLink;
use Datlechin\LinkClicks\Service\TrackableSourceRegistry;
use Datlechin\LinkClicks\ValueObject\RenderContext;
use Datlechin\LinkClicks\ValueObject\TrackedTarget;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use s9e\TextFormatter\Configurator;

/**
 * The template patch that redirects a link, exercised through the real XSL
 * renderer.
 *
 * The integration tests stop at the s9e XML — they never run the XSL-to-HTML
 * pass — so nothing there can tell whether the rewritten `href` actually comes
 * out right. These build a configurator directly and render, which is also a
 * tighter way to cover the part most likely to break: rebuilding each
 * template's original attribute value template as the fallback branch.
 */
class ConfigureTrackableTemplatesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function templates(): array
    {
        return [
            // Shapes taken from the tags this extension actually patches.
            'single expression (URL)' => ['{@url}', 'https://example.com/page'],
            'parameter then attribute (TAGMENTION)' => ['{$BASE_URL}/t/{@slug}', 'https://forum.test/t/support'],
            'literal between expressions (POSTMENTION)' => ['{$BASE_URL}/d/{@discussionid}/{@number}', 'https://forum.test/d/7/3'],
            'trailing literal' => ['{$BASE_URL}/u/{@slug}/posts', 'https://forum.test/u/support/posts'],
        ];
    }

    #[Test]
    #[DataProvider('templates')]
    public function an_untracked_link_keeps_the_templates_own_href(string $hrefTemplate, string $expected): void
    {
        $html = $this->render($hrefTemplate, trackingUrl: null);

        $this->assertStringContainsString('href="'.$expected.'"', $html);
    }

    #[Test]
    #[DataProvider('templates')]
    public function a_tracked_link_renders_the_tracking_url_instead(string $hrefTemplate, string $expected): void
    {
        $html = $this->render($hrefTemplate, trackingUrl: '/lcc/track?u=abc123');

        $this->assertStringContainsString('href="/lcc/track?u=abc123"', $html);
        $this->assertStringNotContainsString('href="'.$expected.'"', $html);
    }

    /**
     * The attribute carrying the tracking URL is read by the template, not
     * copied to the output — leaving it in the HTML would put a second copy of
     * the redirect on every link for no reason.
     */
    #[Test]
    public function the_tracking_attribute_does_not_leak_into_the_output(): void
    {
        $html = $this->render('{@url}', trackingUrl: '/lcc/track?u=abc123');

        $this->assertStringNotContainsString('data-lc-href', $html);
    }

    #[Test]
    public function a_forwarded_attribute_reaches_the_output(): void
    {
        $html = $this->render('{@url}', trackingUrl: '/lcc/track?u=abc123', clicks: '42');

        $this->assertStringContainsString('data-clicks="42"', $html);
    }

    private function render(string $hrefTemplate, ?string $trackingUrl, ?string $clicks = null): string
    {
        $configurator = new Configurator();
        $configurator->rendering->parameters['BASE_URL'] = 'https://forum.test';

        $tag = $configurator->tags->add('LINK');
        $tag->attributes->add('url')->required = false;
        $tag->attributes->add('slug')->required = false;
        $tag->attributes->add('discussionid')->required = false;
        $tag->attributes->add('number')->required = false;
        $tag->template = '<a href="'.$hrefTemplate.'"><xsl:apply-templates/></a>';

        $registry = new TrackableSourceRegistry();
        $registry->add($this->source());

        (new ConfigureTrackableTemplates($registry))($configurator);

        $attrs = 'url="https://example.com/page" slug="support" discussionid="7" number="3"';

        if ($trackingUrl !== null) {
            $attrs .= ' data-lc-href="'.$trackingUrl.'"';
        }

        if ($clicks !== null) {
            $attrs .= ' data-clicks="'.$clicks.'"';
        }

        return $configurator->finalize()['renderer']->render('<r><LINK '.$attrs.'>text</LINK></r>');
    }

    private function source(): TrackableSource
    {
        return new class implements TrackableSource {
            public function key(): string
            {
                return 'link';
            }

            public function tagName(): string
            {
                return 'LINK';
            }

            public function extract(string $parsedXml): array
            {
                return [];
            }

            public function shouldPersist(TrackedTarget $target): bool
            {
                return true;
            }

            public function forwardedAttributes(): array
            {
                return ['data-clicks'];
            }

            public function identify(array $attrs): ?string
            {
                return null;
            }

            public function apply(array $attrs, PostLink $link, string $trackingUrl, RenderContext $context): array
            {
                return $attrs;
            }

            public function resolveTarget(PostLink $link): ?string
            {
                return $link->url;
            }
        };
    }
}
