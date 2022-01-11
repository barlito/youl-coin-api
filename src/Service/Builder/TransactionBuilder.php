<?php

declare(strict_types=1);

namespace App\Service\Builder;

use App\Entity\Transaction;
use App\Message\TransactionMessage;

class TransactionBuilder
{
    public function build(TransactionMessage $transactionMessage): Transaction
    {
        return (new Transaction())
            ->setAmount($transactionMessage->getAmount())
            ->setWalletFrom($transactionMessage->getWalletFrom())
            ->setWalletTo($transactionMessage->getWalletTo())
            ->setType($transactionMessage->getType())
            ->setMessage($transactionMessage->getMessage())
        ;
    }
}
