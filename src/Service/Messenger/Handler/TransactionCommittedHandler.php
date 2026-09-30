<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\DeliverWebhook;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class TransactionCommittedHandler
{
    /**
     * @param list<array{name: string, url: string, secret: string}> $webhookSubscribers
     */
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly TransactionNotificationPublisher $transactionPublisher,
        private readonly MessageBusInterface $bus,
        #[Autowire('%app.webhooks%')]
        private readonly array $webhookSubscribers,
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

        if ([] === $transaction->getPlayerWallets()) {
            return;
        }

        foreach ($this->webhookSubscribers as $subscriber) {
            if ('' !== $subscriber['url']) {
                $this->bus->dispatch(new DeliverWebhook($message->transactionId, $subscriber['name']));
            }
        }
    }
}
