<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Service;

use Datlechin\LinkClicks\ValueObject\NormalizedUrl;
use s9e\TextFormatter\Utils;

class LinkExtractor
{
    public function __construct(
        protected UrlNormalizer $normalizer,
    ) {
    }

    /**
     * @return array<string, NormalizedUrl> keyed by url_hash
     */
    public function extract(string $parsedXml): array
    {
        if ($parsedXml === '' || ! str_contains($parsedXml, '<URL')) {
            return [];
        }

        $urls = [];

        foreach (Utils::getAttributeValues($parsedXml, 'URL', 'url') as $rawUrl) {
            $normalized = $this->normalizer->normalize($rawUrl);
            if ($normalized === null) {
                continue;
            }
            $urls[$normalized->hash] = $normalized;
        }

        return $urls;
    }
}
