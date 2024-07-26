<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\LockEnum;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionHandler extends AbstractHandler
{
    public function __construct(
        private readonly LockFactory $factory,
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
    public function handleTransaction(Transaction $transaction): void
    {
        $lock = $this->getLock();
        $lock->acquire(true);

        try {
            $this->validate($transaction);

            $walletFrom = $this->refreshWallet($transaction->getWalletFrom());
            $walletTo = $this->refreshWallet($transaction->getWalletTo());
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
        } finally {
            $lock->release();
        }
    }

    private function notify(Transaction $transaction): void
    {
        $this->discordNotifier->notifyNewTransaction($transaction);
        $this->transactionPublisher->publishTransactionNotification($transaction);
    }

    private function refreshWallet(Wallet $wallet): Wallet
    {
        $this->entityManager->refresh($wallet);

        return $wallet;
    }

    private function getLock(): LockInterface
    {
        return $this->factory->createLock(LockEnum::TRANSACTION_LOCK->value);
    }
}
