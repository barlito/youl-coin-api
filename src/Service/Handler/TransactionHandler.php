<?php

declare(strict_types=1);

namespace App\Service\Handler;

use App\Entity\ApiUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Repository\TransactionRepository;
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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionHandler extends AbstractHandler
{
    public const string EXTERNAL_IDENTIFIER_CONFLICT = 'This externalIdentifier was already used for a different transaction.';

    public function __construct(
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly TransactionNotificationPublisher $transactionPublisher,
        private readonly TransactionBuilder $transactionBuilder,
        private readonly MoneyUtil $moneyUtil,
        private readonly TransactionRepository $transactionRepository,
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
        try {
            $replayed = $this->entityManager->wrapInTransaction(function () use ($transaction): ?Transaction {
                $this->lockWallets($transaction);

                // A retry must get the original transaction back, even if the balance has changed since.
                $replayed = $this->findReplayedTransaction($transaction);
                if ($replayed instanceof Transaction) {
                    return $replayed;
                }

                // Validated after the lock: the balance check must read the locked rows, not the deserialized ones.
                $this->validate($transaction);
                $this->moveCoins($transaction);
                $this->entityManager->persist($transaction);

                return null;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Same key sent concurrently for other wallets: the replay lookup could not see the other row yet.
            throw new ConflictHttpException(self::EXTERNAL_IDENTIFIER_CONFLICT, $exception);
        }

        if ($replayed instanceof Transaction) {
            return $replayed;
        }

        $this->notify($transaction);

        return $transaction;
    }

    // Mint has no walletFrom, Burn no walletTo: only the wallets present move
    private function moveCoins(Transaction $transaction): void
    {
        $amount = $this->moneyUtil->getMoney((string) $transaction->getAmount());
        $walletFrom = $transaction->getWalletFrom();
        $walletTo = $transaction->getWalletTo();

        if (!$walletFrom instanceof Wallet && !$walletTo instanceof Wallet) {
            throw new \LogicException('A validated transaction always has at least one wallet.');
        }

        if ($walletFrom instanceof Wallet) {
            $walletFrom->setAmount(
                (string)
                $this->moneyUtil->getMoney($walletFrom->getAmount())
                    ->minus($amount)->getMinorAmount()->toInt(),
            );
        }

        if ($walletTo instanceof Wallet) {
            $walletTo->setAmount(
                (string)
                $this->moneyUtil->getMoney($walletTo->getAmount())
                    ->plus($amount)->getMinorAmount()->toInt(),
            );
        }
    }

    private function findReplayedTransaction(Transaction $transaction): ?Transaction
    {
        $issuer = $transaction->getIssuer();
        $externalIdentifier = $transaction->getExternalIdentifier();

        if (!$issuer instanceof ApiUser || null === $externalIdentifier) {
            return null;
        }

        $existing = $this->transactionRepository->findOneBy([
            'issuer' => $issuer,
            'externalIdentifier' => $externalIdentifier,
        ]);

        if (!$existing instanceof Transaction) {
            return null;
        }

        if (!$this->isSamePayload($existing, $transaction)) {
            throw new ConflictHttpException(self::EXTERNAL_IDENTIFIER_CONFLICT);
        }

        return $existing;
    }

    private function isSamePayload(Transaction $existing, Transaction $transaction): bool
    {
        return $existing->getAmount() === $transaction->getAmount()
            && $existing->getType() === $transaction->getType()
            && $existing->getWalletFrom()?->getId() === $transaction->getWalletFrom()?->getId()
            && $existing->getWalletTo()?->getId() === $transaction->getWalletTo()?->getId();
    }

    private function notify(Transaction $transaction): void
    {
        $this->discordNotifier->notifyNewTransaction($transaction);

        // The Discord bot consuming this transport assumes two wallets: Mint/Burn stay Discord-webhook-only
        if (!\in_array($transaction->getType(), [TransactionTypeEnum::MINT, TransactionTypeEnum::BURN], true)) {
            $this->transactionPublisher->publishTransactionNotification($transaction);
        }
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
