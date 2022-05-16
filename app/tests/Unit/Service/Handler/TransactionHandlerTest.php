<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Service\Handler\TransactionHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TransactionHandlerTest extends TestCase
{
    /**
     * @dataProvider getTransactions()
     */
    public function testHandleTransactionWithValidTransactionObject(Transaction $transaction, string $amountWalletFrom, string $amountWalletTo)
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Transaction::class))
        ;

        $entityManager->expects($this->once())
            ->method('flush')
        ;

        $transactionHandler = $this->getTransactionHandler($entityManager);
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

    private function getTransactionHandler($entityManager): TransactionHandler
    {
        return new TransactionHandler(
            $entityManager,
            $this->createMock(DiscordNotifier::class),
        );
    }
}
