<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Service\Handler\TransactionHandler;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\DiscordNotifier;
use App\Service\Util\MoneyUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionHandlerTest extends TestCase
{
    /**
     * @dataProvider getTransactions()
     */
    public function testHandleTransactionWithValidTransactionObject(Transaction $transaction, string $amountWalletFrom, string $amountWalletTo)
    {
        $transactionHandler = $this->getTransactionHandler();

        $transactionHandler->handleTransaction($transaction);

        $this->assertSame($transaction->getWalletFrom()->getAmount(), $amountWalletFrom);
        $this->assertSame($transaction->getWalletTo()->getAmount(), $amountWalletTo);
    }

    private function getTransactions(): array
    {
        $walletFrom = (new Wallet())->setId(Ulid::generate())->setAmount('100000000000');
        $walletTo = (new Wallet())->setId(Ulid::generate())->setAmount('200000000000');

        return [
            [
                (new Transaction())
                    ->setAmount('50000000000')
                    ->setWalletFrom(clone $walletFrom)
                    ->setWalletTo(clone $walletTo),
                '50000000000', '250000000000',
            ],
            [
                (new Transaction())
                    ->setAmount('100000000000')
                    ->setWalletFrom(clone $walletFrom)
                    ->setWalletTo(clone $walletTo),
                '0', '300000000000',
            ],
        ];
    }

    private function getTransactionHandler(): TransactionHandler
    {
        return new TransactionHandler(
            $this->getDiscordNotifierMock(),
            $this->getTransactionNotificationPublisherMock(),
            new MoneyUtil(),
            $this->getEntityManagerMockPersistingOnce(),
            $this->createMock(ValidatorInterface::class),
        );
    }

    private function getDiscordNotifierMock(): MockObject | DiscordNotifier
    {
        $discordNotifierMock = $this->createMock(DiscordNotifier::class);

        $discordNotifierMock->expects($this->once())
            ->method('notifyNewTransaction')
        ;

        return $discordNotifierMock;
    }

    private function getTransactionNotificationPublisherMock(): MockObject | TransactionNotificationPublisher
    {
        $discordNotifierMock = $this->createMock(TransactionNotificationPublisher::class);

        $discordNotifierMock->expects($this->once())
            ->method('publishTransactionNotification')
        ;

        return $discordNotifierMock;
    }

    private function getEntityManagerMockPersistingOnce(): MockObject | EntityManagerInterface
    {
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Transaction::class))
        ;
        $entityManagerMock->expects($this->once())
            ->method('flush')
        ;

        return $entityManagerMock;
    }
}
