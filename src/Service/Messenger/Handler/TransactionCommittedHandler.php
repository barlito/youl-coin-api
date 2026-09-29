<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class TransactionCommittedHandler
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly TransactionNotificationPublisher $transactionPublisher,
    ) {
    }

    public function __invoke(TransactionCommitted $message): void
    {
        $transaction = $this->transactionRepository->find($message->transactionId);
        if (!$transaction instanceof Transaction) {
            throw new UnrecoverableMessageHandlingException(\sprintf('Transaction "%s" not found.', $message->transactionId));
        }

        // At-least-once delivery: a retry after an AMQP failure posts the Discord webhook again
        $this->discordNotifier->notifyNewTransaction($transaction);

        // The Discord bot consuming this transport assumes two wallets: Mint/Burn stay Discord-webhook-only
        if (!$transaction->getType()?->isSupplyChange()) {
            $this->transactionPublisher->publishTransactionNotification($transaction);
        }
    }
}
