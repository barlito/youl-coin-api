<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Builder;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use PHPUnit\Framework\TestCase;

class TransactionBuilderTest extends TestCase
{
    /**
     * @dataProvider getTransactionMessages()
     */
    public function testBuildTransactionWithValidMessage(TransactionMessage $transactionMessage)
    {
        $transactionBuilder = $this->getTransactionBuidler();

        $transaction = $transactionBuilder->build($transactionMessage);

        self::assertSame($transaction->getAmount(), $transactionMessage->getAmount());
        self::assertSame($transaction->getWalletFrom()->getId(), $transactionMessage->getWalletFrom()->getId());
        self::assertSame($transaction->getWalletTo()->getId(), $transactionMessage->getWalletTo()->getId());
        self::assertSame($transaction->getType(), $transactionMessage->getType());
        self::assertSame($transaction->getMessage(), $transactionMessage->getMessage());
    }

    private function getTransactionMessages(): array
    {
        $walletFrom = (new Wallet())->setId('01FS2K2APQXD56RGKG7S911QPH');
        $walletTo = (new Wallet())->setId('01FS2K2PRC3TP0F86955K0SWXT');

        return [
            [new TransactionMessage('300', $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC, 'This is a test transaction')],
        ];
    }

    private function getTransactionBuidler(): TransactionBuilder
    {
        return new TransactionBuilder();
    }
}
