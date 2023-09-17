<?php

declare(strict_types=1);

namespace App\Service\Messenger\Publisher;

use App\Entity\Transaction;
use Symfony\Component\Messenger\MessageBusInterface;

class TransactionNotificationPublisher
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    public function publishTransactionNotification(Transaction $transaction): void
    {
        $this->bus->dispatch($transaction);
    }
}
