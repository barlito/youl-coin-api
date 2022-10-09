<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\Transaction;
use App\Service\Handler\Abstraction\AbstractHandler;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionHandler extends AbstractHandler
{
    public function __construct(
        private readonly TransactionNotifierInterface $discordNotifier,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($entityManager, $validator);
    }

    public function handleTransaction(Transaction $transaction): void
    {
        // TODO need to lock and unlock Wallets during the calculation

        $walletFrom = $transaction->getWalletFrom();
        $walletTo = $transaction->getWalletTo();
        $amount = $transaction->getAmount();

        $walletFrom->setAmount(bcsub($walletFrom->getAmount(), $amount));
        $walletTo->setAmount(bcadd($walletTo->getAmount(), $amount));

        $this->persistOneEntity($transaction);

        $this->notify($transaction);
    }

    private function notify(Transaction $transaction): void
    {
        $this->discordNotifier->notifyNewTransaction($transaction);
    }
}
