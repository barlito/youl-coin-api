<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Message\DeliverWebhook;
use App\Repository\TransactionRepository;
use App\Service\Webhook\WebhookSigner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class DeliverWebhookHandler
{
    /**
     * @param list<array{name: string, url: string, secret: string}> $subscribers
     */
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly WebhookSigner $signer,
        #[Autowire('%app.webhooks%')]
        private readonly array $subscribers,
    ) {
    }

    public function __invoke(DeliverWebhook $message): void
    {
        $subscriber = array_find($this->subscribers, static fn (array $s): bool => $s['name'] === $message->subscriber);
        $transaction = $this->transactionRepository->find($message->transactionId);
        if (null === $subscriber || !$transaction instanceof Transaction || null === $transaction->getCreatedAt()) {
            throw new UnrecoverableMessageHandlingException(\sprintf('Webhook "%s" for transaction "%s" cannot be built.', $message->subscriber, $message->transactionId));
        }

        $body = json_encode([
            'event' => 'transaction.committed',
            'transactionId' => $message->transactionId,
            'type' => $transaction->getType()?->value,
            'amount' => $transaction->getAmount(),
            'createdAt' => \DateTimeImmutable::createFromInterface($transaction->getCreatedAt())->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM),
            'wallets' => array_map(
                static fn (Wallet $wallet): array => ['discordId' => $wallet->getDiscordId(), 'balance' => $wallet->getAmount()],
                $transaction->getPlayerWallets(),
            ),
        ], \JSON_THROW_ON_ERROR);
        $timestamp = time();

        // Non-2xx statuses throw: the outbox retry strategy then the failed transport take over
        $this->httpClient->request('POST', $subscriber['url'], [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                WebhookSigner::TIMESTAMP_HEADER => (string) $timestamp,
                WebhookSigner::SIGNATURE_HEADER => $this->signer->sign($body, $subscriber['secret'], $timestamp),
            ],
        ])->getContent();
    }
}
