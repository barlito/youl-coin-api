<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Webhook;

use App\Service\Webhook\WebhookSigner;
use PHPUnit\Framework\TestCase;

class WebhookSignerTest extends TestCase
{
    public function testItSignsTheTimestampAndTheRawBodyWithHmacSha256(): void
    {
        $signature = new WebhookSigner()->sign('{"a":1}', 'secret', 1700000000);

        $this->assertSame('sha256=' . hash_hmac('sha256', '1700000000.{"a":1}', 'secret'), $signature);
    }

    public function testItVerifiesOnlyAnUntamperedSignature(): void
    {
        $signer = new WebhookSigner();
        $signature = $signer->sign('body', 'secret', 10);

        $this->assertTrue($signer->verify($signature, 'body', 'secret', 10));
        $this->assertFalse($signer->verify($signature, 'tampered', 'secret', 10));
        $this->assertFalse($signer->verify($signature, 'body', 'other', 10));
        $this->assertFalse($signer->verify($signature, 'body', 'secret', 11));
    }
}
