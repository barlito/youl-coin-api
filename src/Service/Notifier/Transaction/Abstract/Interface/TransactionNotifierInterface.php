<?php

declare(strict_types=1);

namespace App\Service\Notifier\Transaction\Abstract\Interface;

use App\Entity\Transaction;

interface TransactionNotifierInterface
{
    public function notifyNewTransaction(Transaction $transaction): void;

    public function notifyErrorOnTransaction(string $errorMessage, string $messageContent): void;
}
