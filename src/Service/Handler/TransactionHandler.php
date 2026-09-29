<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\Abstraction\AbstractHandler;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use App\Service\Util\MoneyUtil;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionHandler extends AbstractHandler
{
    public function __construct(
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly TransactionNotificationPublisher $transactionPublisher,
        private readonly TransactionBuilder $transactionBuilder,
        private readonly MoneyUtil $moneyUtil,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($entityManager, $validator);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function handleTransactionMessage(TransactionMessage $transactionMessage): void
    {
        $transaction = $this->transactionBuilder->buildFromTransactionMessage($transactionMessage);

        $this->handleTransaction($transaction);
    }

    /**
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function handleTransaction(Transaction $transaction): Transaction
    {
        $this->entityManager->wrapInTransaction(function () use ($transaction): void {
            $this->lockWallets($transaction);
            // Validated after the lock: the balance check must read the locked rows, not the deserialized ones.
            $this->validate($transaction);

            $amount = $this->moneyUtil->getMoney((string) $transaction->getAmount());
            $walletFrom = $transaction->getWalletFrom();
            $walletTo = $transaction->getWalletTo();

            if (!$walletFrom instanceof Wallet || !$walletTo instanceof Wallet) {
                throw new \LogicException('A validated transaction always has both wallets.');
            }

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

            $this->entityManager->persist($transaction);
        });

        $this->notify($transaction);

        return $transaction;
    }

    private function notify(Transaction $transaction): void
    {
        $this->discordNotifier->notifyNewTransaction($transaction);
        $this->transactionPublisher->publishTransactionNotification($transaction);
    }

    /**
     * SELECT ... FOR UPDATE on both wallets, in id order so two opposite transfers cannot deadlock.
     */
    private function lockWallets(Transaction $transaction): void
    {
        $wallets = [];
        foreach ([$transaction->getWalletFrom(), $transaction->getWalletTo()] as $wallet) {
            if ($wallet instanceof Wallet && null !== $wallet->getId()) {
                $wallets[$wallet->getId()] = $wallet;
            }
        }

        ksort($wallets, SORT_STRING);

        foreach ($wallets as $wallet) {
            $this->entityManager->refresh($wallet, LockMode::PESSIMISTIC_WRITE);
        }
    }
}
