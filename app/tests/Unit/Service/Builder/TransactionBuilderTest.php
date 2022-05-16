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
        $transactionBuilder = $this->getTransactionBuilder();

        $transaction = $transactionBuilder->build($transactionMessage);

        $this->assertSame($transaction->getAmount(), $transactionMessage->getAmount());
        $this->assertSame($transaction->getWalletFrom()->getId(), $transactionMessage->getWalletFrom()->getId());
        $this->assertSame($transaction->getWalletTo()->getId(), $transactionMessage->getWalletTo()->getId());
        $this->assertSame($transaction->getType(), $transactionMessage->getType());
        $this->assertSame($transaction->getMessage(), $transactionMessage->getMessage());
    }

    private function getTransactionMessages(): array
    {
        $walletFrom = (new Wallet())->setId('01FS2K2APQXD56RGKG7S911QPH');
        $walletTo = (new Wallet())->setId('01FS2K2PRC3TP0F86955K0SWXT');

        return [
            [new TransactionMessage('300', $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC, 'This is a test transaction')],
        ];
    }

    private function getTransactionBuilder(): TransactionBuilder
    {
        return new TransactionBuilder();
    }
}
