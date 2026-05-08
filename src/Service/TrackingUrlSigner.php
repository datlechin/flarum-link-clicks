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

use Datlechin\LinkClicks\ValueObject\SignedPayload;

class TrackingUrlSigner
{
    private const ALGO = 'sha256';

    /**
     * Tokens are valid for one year. The TTL is a constant rather than a
     * setting because it's a security parameter (replay-attack guard after
     * key rotation), not an operational knob. Admins should rotate the key.
     */
    private const TTL_SECONDS = 31_536_000;

    public function __construct(
        protected HmacKeyProvider $keyProvider,
    ) {
    }

    public function sign(int $postLinkId): string
    {
        $payload = json_encode(
            ['id' => $postLinkId, 'exp' => time() + self::TTL_SECONDS],
            JSON_THROW_ON_ERROR,
        );

        $mac = hash_hmac(self::ALGO, $payload, $this->keyProvider->get(), binary: true);

        return $this->base64UrlEncode($mac.'.'.$payload);
    }

    public function verify(string $token): ?SignedPayload
    {
        $raw = $this->base64UrlDecode($token);
        if ($raw === null || strlen($raw) < 33 || $raw[32] !== '.') {
            return null;
        }

        $mac = substr($raw, 0, 32);
        $payload = substr($raw, 33);

        $expected = hash_hmac(self::ALGO, $payload, $this->keyProvider->get(), binary: true);
        if (! hash_equals($expected, $mac)) {
            return null;
        }

        try {
            $data = json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data)
            || ! isset($data['id'], $data['exp'])
            || ! is_int($data['id'])
            || ! is_int($data['exp'])
            || $data['exp'] < time()
        ) {
            return null;
        }

        return new SignedPayload(postLinkId: $data['id']);
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $token): ?string
    {
        $padded = strtr($token, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $decoded = base64_decode($padded, strict: true);

        return $decoded === false ? null : $decoded;
    }
}
