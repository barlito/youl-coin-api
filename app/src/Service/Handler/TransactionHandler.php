<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\Transaction;
use App\Service\Handler\Abstraction\AbstractHandler;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use App\Service\Util\MoneyUtil;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionHandler extends AbstractHandler
{
    public function __construct(
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly MoneyUtil $moneyUtil,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($entityManager, $validator);
    }

    /**
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function handleTransaction(Transaction $transaction): void
    {
        $this->validate($transaction);

        $walletFrom = $transaction->getWalletFrom();
        $walletTo = $transaction->getWalletTo();
        $amount = $this->moneyUtil->getMoney($transaction->getAmount());

        $walletFrom->setAmount(
            (string)
            $this->moneyUtil->getMoney($walletFrom->getAmount())
                ->minus($amount)->getMinorAmount()->toInt(),
        );

        $walletTo->setAmount(
            (string)
            $this->moneyUtil->getMoney($walletTo->getAmount())
                ->plus($amount)->getMinorAmount()->toInt(),
        );

        $this->persistOneEntity($transaction);

        $this->notify($transaction);
    }

    private function notify(Transaction $transaction): void
    {
        $this->discordNotifier->notifyNewTransaction($transaction);
    }
}
