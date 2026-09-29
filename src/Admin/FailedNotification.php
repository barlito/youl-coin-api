<?php

declare(strict_types=1);

namespace App\Admin;

final readonly class FailedNotification
{
    public function __construct(
        public ?string $id,
        public ?\DateTimeInterface $failedAt,
        public string $type,
        public ?string $transactionUrl,
        public ?string $transactionId,
        public string $error,
        public int $retries,
    ) {
    }
}
