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

        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Transaction::class))
        ;

        $entityManager->expects(self::atLeastOnce())
            ->method('flush')
        ;

        $transactionProcessor = $this->getTransactionProcessor($entityManager);

        $transactionProcessor->handleTransaction($transaction);

        self::assertSame($transaction->getWalletFrom()->getAmount(), $amountWalletFrom);
        self::assertSame($transaction->getWalletTo()->getAmount(), $amountWalletTo);
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

    private function getTransactionProcessor($entityManager): TransactionHandler
    {
        return new TransactionHandler(
            $entityManager,
            $this->createMock(DiscordNotifier::class),
        );
    }
}
