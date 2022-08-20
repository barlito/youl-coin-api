<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Service\Handler\TransactionHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
        $walletFrom = (new Wallet())->setAmount('1000');
        $walletTo = (new Wallet())->setAmount('2000');

        return [
            [
                (new Transaction())
                    ->setAmount('500')
                    ->setWalletFrom(clone $walletFrom)
                    ->setWalletTo(clone $walletTo),
                '500', '2500',
            ],
            [
                (new Transaction())
                    ->setAmount('1000')
                    ->setWalletFrom(clone $walletFrom)
                    ->setWalletTo(clone $walletTo),
                '0', '3000',
            ],
        ];
    }

    private function getTransactionHandler(): TransactionHandler
    {
        return new TransactionHandler(
            $this->getDiscordNotifierMock(),
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
