<?php

declare(strict_types=1);

namespace App\Service\Builder;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Message\TransactionMessage;
use App\Repository\DiscordUserRepository;
use App\Repository\WalletRepository;
use Doctrine\DBAL\LockMode;

class TransactionBuilder
{
    public function __construct(private readonly DiscordUserRepository $discordUserRepository, private readonly WalletRepository $walletRepository)
    {
    }

    public function build(TransactionMessage $transactionMessage): Transaction
    {
        return (new Transaction())
            ->setAmount($transactionMessage->getAmount())
            ->setWalletFrom($this->findWallet($transactionMessage->getDiscordUserIdFrom()))
            ->setWalletTo($this->findWallet($transactionMessage->getDiscordUserIdTo()))
            ->setType($transactionMessage->getType())
            ->setMessage($transactionMessage->getMessage())
        ;
    }

    private function findWallet(string $discordUserId): ?Wallet
    {
        // Request seems weird, but needed to put a lock on Wallet table
        return $this->walletRepository->find(
            $this->discordUserRepository->find($discordUserId)->getWallet()->getId(),
            LockMode::PESSIMISTIC_WRITE,
        );
    }
}
