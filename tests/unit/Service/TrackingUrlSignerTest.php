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

use Datlechin\LinkClicks\Service\HmacKeyProvider;
use Datlechin\LinkClicks\Service\TrackingUrlSigner;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TrackingUrlSignerTest extends TestCase
{
    private TrackingUrlSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new TrackingUrlSigner($this->keyProvider('this-is-a-deterministic-test-key'));
    }

    #[Test]
    public function it_round_trips_a_post_link_id(): void
    {
        $token = $this->signer->sign(42);
        $payload = $this->signer->verify($token);

        $this->assertNotNull($payload);
        $this->assertSame(42, $payload->postLinkId);
    }

    #[Test]
    public function it_produces_url_safe_tokens(): void
    {
        $token = $this->signer->sign(1);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
    }

    #[Test]
    public function tampered_payload_fails_verification(): void
    {
        $token = $this->signer->sign(42);

        // Flip one byte in the middle of the token (lands in the JSON portion).
        $tampered = substr_replace($token, $this->flipChar($token[40]), 40, 1);

        $this->assertNotSame($token, $tampered);
        $this->assertNull($this->signer->verify($tampered));
    }

    #[Test]
    public function tampered_mac_fails_verification(): void
    {
        $token = $this->signer->sign(42);
        $tampered = substr_replace($token, $this->flipChar($token[2]), 2, 1);

        $this->assertNull($this->signer->verify($tampered));
    }

    #[Test]
    public function token_signed_with_a_different_key_fails_verification(): void
    {
        $alice = new TrackingUrlSigner($this->keyProvider('key-alice'));
        $bob = new TrackingUrlSigner($this->keyProvider('key-bob'));

        $token = $alice->sign(42);
        $this->assertNull($bob->verify($token));
    }

    #[Test]
    public function malformed_base64_fails_verification(): void
    {
        $this->assertNull($this->signer->verify('not!valid!base64!@#'));
        $this->assertNull($this->signer->verify(''));
        $this->assertNull($this->signer->verify('a'));
    }

    #[Test]
    public function truncated_token_fails_verification(): void
    {
        $token = $this->signer->sign(42);
        $this->assertNull($this->signer->verify(substr($token, 0, 20)));
    }

    private function keyProvider(string $key): HmacKeyProvider
    {
        // Anonymous subclass that bypasses settings/db and returns a fixed key.
        return new class($key) extends HmacKeyProvider {
            public function __construct(private readonly string $key)
            {
            }

            public function get(): string
            {
                return $this->key;
            }
        };
    }

    private function flipChar(string $char): string
    {
        // Map any char to a different valid base64url char so tampering stays
        // syntactically valid (forces verification through to MAC check).
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $pos = strpos($alphabet, $char);
        $next = $alphabet[($pos === false ? 0 : $pos + 1) % strlen($alphabet)];

        return $next;
    }
}
