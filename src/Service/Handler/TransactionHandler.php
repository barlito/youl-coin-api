<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\Transaction;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;

class TransactionHandler
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DiscordNotifier        $discordNotifier,
    ) {
    }

    public function handleTransaction(Transaction $transaction)
    {
        //TODO need to lock and unlock Wallets during the calculation

        $walletFrom = $transaction->getWalletFrom();
        $walletTo   = $transaction->getWalletTo();
        $amount     = $transaction->getAmount();

        $walletFrom->setAmount(bcsub($walletFrom->getAmount(), $amount));
        $walletTo->setAmount(bcadd($walletTo->getAmount(), $amount));

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->notify($transaction);
    }

    private function notify(Transaction $transaction)
    {
        //TODO dispatch an event and handle the discord notif with a subscriber
        $this->discordNotifier->notifyNewTransaction($transaction);
    }
}