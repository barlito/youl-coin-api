<?php

declare(strict_types=1);

namespace App\Message;

// Written to the outbox in the same DB transaction as the money movement
final readonly class TransactionCommitted
{
    public function __construct(public string $transactionId)
    {
    }
}
