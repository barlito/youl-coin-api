<?php

declare(strict_types=1);

namespace App\Service\Builder;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Repository\WalletRepository;

class TransactionBuilder
{
    public function __construct(private readonly WalletRepository $walletRepository)
    {
    }

    public function buildFromTransactionMessage(TransactionMessage $transactionMessage): Transaction
    {
        return (new Transaction())
            ->setAmount($transactionMessage->getAmount())
            ->setWalletFrom($this->walletRepository->findOneBy(['discordUser' => $transactionMessage->getDiscordUserIdFrom()]))
            ->setWalletTo($this->walletRepository->findOneBy(['discordUser' => $transactionMessage->getDiscordUserIdTo()]))
            ->setType($transactionMessage->getType())
            ->setMessage($transactionMessage->getMessage())
        ;
    }

    public function buildFromBankWalletTransaction(BankWalletTransactionDTO $bankWalletTransaction): Transaction
    {
        return (new Transaction())
            ->setAmount($this->getFullAmount((string) $bankWalletTransaction->getAmount()))
            ->setWalletFrom($bankWalletTransaction->getWalletFrom())
            ->setWalletTo($bankWalletTransaction->getWalletTo())
            ->setType(TransactionTypeEnum::VALUES[$bankWalletTransaction->getTransactionType()])
            ->setMessage($bankWalletTransaction->getTransactionNotes())
        ;
    }

    private function getFullAmount(string|int $amount): string
    {
        return $amount . '00000000';
    }
}
