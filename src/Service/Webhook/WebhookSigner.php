<?php

declare(strict_types=1);

namespace App\Service\Webhook;

class WebhookSigner
{
    public const string TIMESTAMP_HEADER = 'X-Youl-Timestamp';
    public const string SIGNATURE_HEADER = 'X-Youl-Signature';

    public function sign(string $body, string $secret, int $timestamp): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public function verify(string $signature, string $body, string $secret, int $timestamp): bool
    {
        return hash_equals($this->sign($body, $secret, $timestamp), $signature);
    }
}
