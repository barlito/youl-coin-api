<?php

declare(strict_types=1);

namespace App\Service\WelcomeBonus;

use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Repository\EconomySettingsRepository;
use App\Repository\TransactionRepository;
use App\Repository\WalletRepository;
use App\Service\Handler\TransactionHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

// Grants the bank-funded welcome bonus at login (DiscordAuthenticator); this login also signs players into youl-tcg, so nothing here may ever throw out
class WelcomeBonusService
{
    private const string ELIGIBILITY_WINDOW = '-30 days';

    public function __construct(
        private readonly EconomySettingsRepository $economySettingsRepository,
        private readonly WalletRepository $walletRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionHandler $transactionHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function grantIfEligible(DiscordUser $user): void
    {
        $wallet = $user->getWallet();
        if (!$wallet instanceof Wallet) {
            return;
        }

        $transaction = null;

        try {
            $transaction = $this->buildTransaction($wallet);
            if ($transaction instanceof Transaction) {
                $this->transactionHandler->handleTransaction($transaction);
            }
        } catch (ConflictHttpException $exception) {
            // The partial unique index is the only conflict a welcome bonus can hit: a concurrent login granted it first
            $this->logger->warning('Welcome bonus already granted by a concurrent login.', ['discordId' => $user->getDiscordId(), 'exception' => $exception]);
        } catch (\Throwable $exception) {
            $context = ['discordId' => $user->getDiscordId(), 'exception' => $exception];

            // A managed transaction means the commit succeeded and only the notification failed
            if ($transaction instanceof Transaction && $this->entityManager->contains($transaction)) {
                $this->logger->warning('Welcome bonus granted but its notification failed.', $context);
            } else {
                $this->logger->error('Welcome bonus not granted, login continues.', $context);
            }
        }
    }

    private function buildTransaction(Wallet $wallet): ?Transaction
    {
        $settings = $this->economySettingsRepository->find(EconomySettings::SINGLETON_ID);
        if (!$settings instanceof EconomySettings) {
            $this->logger->error('Welcome bonus disabled: the economy settings row is missing.');

            return null;
        }

        $amount = $settings->getWelcomeBonusAmount();
        if (!is_numeric($amount) || bccomp($amount, '0') <= 0 || !$this->isEligible($wallet, $settings)) {
            return null;
        }

        $bankWallet = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        if (!$bankWallet instanceof Wallet) {
            throw new \RuntimeException('No bank wallet configured.');
        }

        return new Transaction()
            ->setAmount($amount)
            ->setType(TransactionTypeEnum::WELCOME_BONUS)
            ->setWalletFrom($bankWallet)
            ->setWalletTo($wallet)
        ;
    }

    // Pre-check only: the partial unique index on transaction(wallet_to_id) is the real, race-proof guarantee
    private function isEligible(Wallet $wallet, EconomySettings $settings): bool
    {
        $createdAt = $wallet->getCreatedAt();
        $eligibleSince = max($settings->getWelcomeBonusSince(), $this->clock->now()->modify(self::ELIGIBILITY_WINDOW));
        if (!$createdAt instanceof \DateTimeInterface || $createdAt < $eligibleSince) {
            return false;
        }

        return null === $this->transactionRepository->findOneBy([
            'walletTo' => $wallet,
            'type' => TransactionTypeEnum::WELCOME_BONUS,
        ]);
    }
}
