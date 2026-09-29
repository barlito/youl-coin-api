<?php

declare(strict_types=1);

namespace App\Service\WelcomeBonus;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Repository\TransactionRepository;
use App\Repository\WalletRepository;
use App\Service\Economy\EconomySettingsProvider;
use App\Service\Handler\TransactionHandler;
use Psr\Log\LoggerInterface;

// Grants the bank-funded welcome bonus at login (DiscordAuthenticator); this login also signs players into youl-tcg, so nothing here may ever throw out
class WelcomeBonusService
{
    private const string ELIGIBILITY_WINDOW = '-30 days';

    public function __construct(
        private readonly EconomySettingsProvider $economySettingsProvider,
        private readonly WalletRepository $walletRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionHandler $transactionHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function grantIfEligible(DiscordUser $user): void
    {
        try {
            $this->doGrant($user);
        } catch (\Throwable $exception) {
            // Bank empty, EntityManager closed by a previous failure, unique index race... login must go on regardless
            $this->logger->warning('Welcome bonus not granted, login continues.', [
                'discordId' => $user->getDiscordId(),
                'exception' => $exception,
            ]);
        }
    }

    private function doGrant(DiscordUser $user): void
    {
        $wallet = $user->getWallet();
        // Unpersisted transient wallet: its own creation already failed and was logged, nothing to credit
        if (!$wallet instanceof Wallet || null === $wallet->getId()) {
            return;
        }

        if (!$this->isEligible($wallet)) {
            return;
        }

        $amount = $this->economySettingsProvider->getWelcomeBonusAmount();
        if (!is_numeric($amount) || bccomp($amount, '0') <= 0) {
            return;
        }

        $bankWallet = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        if (!$bankWallet instanceof Wallet) {
            $this->logger->error('Welcome bonus not granted: no bank wallet configured.', ['discordId' => $user->getDiscordId()]);

            return;
        }

        $transaction = new Transaction()
            ->setAmount($amount)
            ->setType(TransactionTypeEnum::WELCOME_BONUS)
            ->setWalletFrom($bankWallet)
            ->setWalletTo($wallet)
            ->setExternalIdentifier('welcome:' . $user->getDiscordId())
        ;

        $this->transactionHandler->handleTransaction($transaction);
    }

    // Pre-check only: the partial unique index on transaction(wallet_to_id) is the real, race-proof guarantee
    private function isEligible(Wallet $wallet): bool
    {
        $createdAt = $wallet->getCreatedAt();
        if (!$createdAt instanceof \DateTimeInterface || $createdAt < new \DateTimeImmutable(self::ELIGIBILITY_WINDOW)) {
            return false;
        }

        return null === $this->transactionRepository->findOneBy([
            'walletTo' => $wallet,
            'type' => TransactionTypeEnum::WELCOME_BONUS,
        ]);
    }
}
