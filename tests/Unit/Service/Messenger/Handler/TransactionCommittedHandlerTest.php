<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Message\DeliverWebhook;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Handler\TransactionCommittedHandler;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

class TransactionCommittedHandlerTest extends TestCase
{
    #[DataProvider('publishedTypes')]
    public function testItNotifiesDiscordAndPublishesOnAmqpAccordingToTheType(TransactionTypeEnum $type, int $expectedPublications): void
    {
        $transaction = new Transaction()->setType($type);
        $notifier = $this->createMock(TransactionNotifierInterface::class);
        $notifier->expects($this->once())->method('notifyNewTransaction')->with($transaction);
        $publisher = $this->createMock(TransactionNotificationPublisher::class);
        $publisher->expects($this->exactly($expectedPublications))->method('publishTransactionNotification');

        $this->handler($transaction, $notifier, $publisher)(new TransactionCommitted('id'));
    }

    /**
     * @return iterable<string, array{TransactionTypeEnum, int}>
     */
    public static function publishedTypes(): iterable
    {
        yield 'classic' => [TransactionTypeEnum::CLASSIC, 1];
        yield 'welcome bonus' => [TransactionTypeEnum::WELCOME_BONUS, 1];
        yield 'mint' => [TransactionTypeEnum::MINT, 0];
        yield 'burn' => [TransactionTypeEnum::BURN, 0];
    }

    #[DataProvider('webhookCases')]
    public function testItDispatchesOneWebhookPerConfiguredSubscriberForPlayerTransactions(Transaction $transaction, int $expectedDispatches): void
    {
        $subscribers = [
            ['name' => 'ytcg', 'url' => 'http://ytcg/hook', 'secret' => 's'],
            ['name' => 'other', 'url' => 'http://other/hook', 'secret' => 's'],
            ['name' => 'disabled', 'url' => '', 'secret' => ''],
        ];
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly($expectedDispatches))->method('dispatch')->willReturnCallback(
            static function (DeliverWebhook $message) use (&$dispatched): Envelope {
                $dispatched[] = $message->subscriber;

                return new Envelope($message);
            },
        );

        $this->handler($transaction, bus: $bus, subscribers: $subscribers)(new TransactionCommitted('id'));

        $this->assertSame(0 === $expectedDispatches ? [] : ['ytcg', 'other'], $dispatched);
    }

    /**
     * @return iterable<string, array{Transaction, int}>
     */
    public static function webhookCases(): iterable
    {
        $player = new Wallet()->setType(WalletTypeEnum::USER);
        $bank = new Wallet()->setType(WalletTypeEnum::BANK);

        yield 'bank to player' => [new Transaction()->setType(TransactionTypeEnum::CLASSIC)->setWalletFrom($bank)->setWalletTo($player), 2];
        yield 'player to bank' => [new Transaction()->setType(TransactionTypeEnum::CLASSIC)->setWalletFrom($player)->setWalletTo($bank), 2];
        yield 'mint' => [new Transaction()->setType(TransactionTypeEnum::MINT)->setWalletTo($bank), 0];
        yield 'burn' => [new Transaction()->setType(TransactionTypeEnum::BURN)->setWalletFrom($bank), 0];
    }

    public function testAMissingTransactionIsNotRetried(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler(null)(new TransactionCommitted('missing'));
    }

    /**
     * @param list<array{name: string, url: string, secret: string}> $subscribers
     */
    private function handler(
        ?Transaction $transaction,
        ?TransactionNotifierInterface $notifier = null,
        ?TransactionNotificationPublisher $publisher = null,
        ?MessageBusInterface $bus = null,
        array $subscribers = [],
    ): TransactionCommittedHandler {
        $repository = $this->createStub(TransactionRepository::class);
        $repository->method('find')->willReturn($transaction);

        return new TransactionCommittedHandler(
            $repository,
            $notifier ?? $this->createStub(TransactionNotifierInterface::class),
            $publisher ?? $this->createStub(TransactionNotificationPublisher::class),
            $bus ?? $this->createStub(MessageBusInterface::class),
            $subscribers,
        );
    }
}
