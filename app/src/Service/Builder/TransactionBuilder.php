<?php

declare(strict_types=1);

namespace App\Service\Builder;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Message\TransactionMessage;
use App\Repository\WalletRepository;

class TransactionBuilder
{
    public function __construct(private readonly WalletRepository $walletRepository)
    {
    }

    public function buildFromTransactionMessage(TransactionMessage $transactionMessage): Transaction
    {
        $walletFrom = $this->findWallet($transactionMessage->getDiscordUserIdFrom());
        $walletTo = $this->findWallet($transactionMessage->getDiscordUserIdTo());

        return (new Transaction())
            ->setAmount($transactionMessage->getAmount())
            ->setWalletFrom($walletFrom)
            ->setWalletTo($walletTo)
            ->setType($transactionMessage->getType())
            ->setExternalIdentifier($transactionMessage->getExternalIdentifier())
        ;
    }

    public function buildFromBankWalletTransaction(BankWalletTransactionDTO $bankWalletTransaction): Transaction
    {
        return (new Transaction())
            ->setAmount($this->getFullAmount((string) $bankWalletTransaction->getAmount()))
            ->setWalletFrom($bankWalletTransaction->getWalletFrom())
            ->setWalletTo($bankWalletTransaction->getWalletTo())
            ->setType($bankWalletTransaction->getTransactionType())
            ->setExternalIdentifier($bankWalletTransaction->getTransactionNotes())
        ;
    }

    private function getFullAmount(string | int $amount): string
    {
        return $amount . '00000000';
    }

    private function findWallet(?string $getDiscordUserIdFrom): ?Wallet
    {
        if (WalletTypeEnum::BANK->value === $getDiscordUserIdFrom) {
            return $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        }

        return $this->walletRepository->findOneBy(['discordUser' => $getDiscordUserIdFrom]);
    }
}
