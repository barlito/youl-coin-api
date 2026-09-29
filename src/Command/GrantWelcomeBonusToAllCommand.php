<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Repository\DiscordUserRepository;
use App\Repository\EconomySettingsRepository;
use App\Repository\WalletRepository;
use App\Security\DiscordUserWhitelist;
use App\Service\Util\MoneyUtil;
use App\Service\Wallet\UserWalletFactory;
use App\Service\WelcomeBonus\WelcomeBonusService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:welcome-bonus:grant-all', description: 'Creates the missing wallets and grants the welcome bonus to every whitelisted player who never got it')]
class GrantWelcomeBonusToAllCommand extends Command
{
    public function __construct(
        private readonly DiscordUserRepository $discordUserRepository,
        private readonly DiscordUserWhitelist $discordUserWhitelist,
        private readonly EconomySettingsRepository $economySettingsRepository,
        private readonly WalletRepository $walletRepository,
        private readonly WelcomeBonusService $welcomeBonusService,
        private readonly UserWalletFactory $userWalletFactory,
        private readonly MoneyUtil $moneyUtil,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Shows what would be done without writing anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $settings = $this->economySettingsRepository->find(EconomySettings::SINGLETON_ID);
        $amount = $settings?->getWelcomeBonusAmount();
        if (!is_numeric($amount) || bccomp($amount, '0') <= 0) {
            $io->error('Le bonus de bienvenue est désactivé (montant à 0 ou réglage absent) : rien à faire.');

            return Command::FAILURE;
        }

        $users = array_filter(
            $this->discordUserRepository->findBy([], ['username' => 'ASC']),
            fn (DiscordUser $user): bool => $this->discordUserWhitelist->isAllowed($user->getDiscordId()),
        );
        $pending = array_filter($users, fn (DiscordUser $user): bool => !$this->hasReceivedBonus($user));

        $required = bcmul($amount, (string) \count($pending));
        $bank = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        if (!$bank instanceof Wallet) {
            $io->error('Aucun wallet banque : rien n\'a été modifié.');

            return Command::FAILURE;
        }

        $bankAmount = $bank->getAmount();
        if (!is_numeric($bankAmount) || bccomp($bankAmount, $required) < 0) {
            $io->error(\sprintf(
                'MINT d\'au moins %s coins nécessaire (banque : %s, besoin : %s) : rien n\'a été modifié.',
                $this->coins(is_numeric($bankAmount) ? bcsub($required, $bankAmount) : $required),
                $this->coins($bankAmount),
                $this->coins($required),
            ));

            return Command::FAILURE;
        }

        $rows = [];
        $granted = 0;
        $failures = 0;
        foreach ($users as $user) {
            $isPending = \in_array($user, $pending, true);
            $walletMissing = !$user->getWallet() instanceof Wallet;
            $result = $dryRun ? ($isPending ? 'à verser' : 'déjà reçu') : $this->grant($user->getDiscordId(), $isPending ? $amount : null);
            $granted += \in_array($result, ['à verser', 'versé'], true) ? 1 : 0;
            $failures += 'échec' === $result ? 1 : 0;
            $rows[] = [$user->getUsername(), $walletMissing ? ($dryRun ? 'à créer' : 'créé') : 'existant', $result];
        }

        $io->table(['Joueur', 'Wallet', 'Bonus'], $rows);
        $io->text(\sprintf(
            '%d joueur(s) whitelisté(s), %d bonus %s pour %s coins.',
            \count($users),
            $granted,
            $dryRun ? 'à verser' : 'versé(s)',
            $this->coins(bcmul($amount, (string) $granted)),
        ));

        if ($failures > 0) {
            $io->error(\sprintf('%d versement(s) en échec (voir les logs) : relancer la commande pour les reprendre.', $failures));

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run : rien n\'a été modifié.' : 'Terminé.');

        return Command::SUCCESS;
    }

    // Reloads the player by id: a refused transaction closes the EntityManager, which is then reset for the next player
    private function grant(string $discordId, ?string $amount): string
    {
        try {
            $user = $this->discordUserRepository->find($discordId);
            if (!$user instanceof DiscordUser) {
                return 'échec';
            }

            $wallet = $user->getWallet();
            if (!$wallet instanceof Wallet) {
                $wallet = $this->userWalletFactory->create($user);
                $this->entityManager->persist($wallet);
                $this->entityManager->flush();
            }

            if (null === $amount) {
                return 'déjà reçu';
            }

            $this->welcomeBonusService->grant($wallet, $amount);

            return 'versé';
        } catch (\Throwable $exception) {
            $this->logger->error('Welcome bonus grant-all failed for a player.', ['discordId' => $discordId, 'exception' => $exception]);
            $this->managerRegistry->resetManager();

            return 'échec';
        }
    }

    private function hasReceivedBonus(DiscordUser $user): bool
    {
        $wallet = $user->getWallet();

        return $wallet instanceof Wallet && $this->welcomeBonusService->hasReceived($wallet);
    }

    private function coins(string $minor): string
    {
        return $this->moneyUtil->minorToCoins($minor);
    }
}
