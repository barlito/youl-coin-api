<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Handler\TransactionCommittedHandler;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

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

    public function testAMissingTransactionIsNotRetried(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler(null)(new TransactionCommitted('missing'));
    }

    private function handler(
        ?Transaction $transaction,
        ?TransactionNotifierInterface $notifier = null,
        ?TransactionNotificationPublisher $publisher = null,
    ): TransactionCommittedHandler {
        $repository = $this->createStub(TransactionRepository::class);
        $repository->method('find')->willReturn($transaction);

        return new TransactionCommittedHandler(
            $repository,
            $notifier ?? $this->createStub(TransactionNotifierInterface::class),
            $publisher ?? $this->createStub(TransactionNotificationPublisher::class),
        );
    }
}
