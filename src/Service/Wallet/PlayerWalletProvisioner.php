<?php

declare(strict_types=1);

namespace App\Service\Wallet;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Service\WelcomeBonus\WelcomeBonusService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

// Runs on every authentication path, which also signs players into youl-tcg: it must never throw
class PlayerWalletProvisioner implements ResetInterface
{
    /** @var array<string, true> */
    private array $provisioned = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserWalletFactory $userWalletFactory,
        private readonly WelcomeBonusService $welcomeBonusService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function provision(DiscordUser $user): void
    {
        $discordId = $user->getDiscordId();
        if (isset($this->provisioned[$discordId]) || !$this->entityManager->isOpen()) {
            return;
        }
        $this->provisioned[$discordId] = true;

        if (!$user->getWallet() instanceof Wallet && !$this->createWallet($user)) {
            return;
        }

        if ($this->entityManager->isOpen()) {
            $this->welcomeBonusService->grantIfEligible($user);
        }
    }

    public function reset(): void
    {
        $this->provisioned = [];
    }

    private function createWallet(DiscordUser $user): bool
    {
        try {
            $this->entityManager->persist($this->userWalletFactory->create($user));
            $this->entityManager->flush();

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Wallet creation at login failed.', ['discordId' => $user->getDiscordId(), 'exception' => $exception]);

            return false;
        }
    }
}
