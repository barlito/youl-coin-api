<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AllowedDiscordUser;
use App\Entity\ApiUser;
use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Service\Admin\EconomyStatsProvider;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly array $adminUrls,
        private readonly EconomyStatsProvider $economyStatsProvider,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    #[\Override]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', ['dashboard' => $this->economyStatsProvider->provide()]);
    }

    #[\Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('YoulCoin Exchange Admin')
            ->setFaviconPath('https://www.creativefabrica.com/wp-content/uploads/2019/03/Monogram-YC-Logo-Design-by-Greenlines-Studios.jpg')
            ->renderContentMaximized()
        ;
    }

    #[\Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Access');
        yield MenuItem::linkToCrud('API Users', 'fa-solid fa-globe', ApiUser::class);
        yield MenuItem::linkToCrud('Discord Whitelist', 'fa-brands fa-discord', AllowedDiscordUser::class);
        yield MenuItem::linkToCrud('Joueurs', 'fas fa-users', DiscordUser::class);

        yield MenuItem::section('Wallet Settings');
        yield MenuItem::linkToCrud('Wallets', 'fas fa-wallet', Wallet::class);
        yield MenuItem::linkToCrud('Transactions', 'fas fa-right-left', Transaction::class);
        yield MenuItem::linktoRoute('Bank Wallet', 'fa fa-chart-bar', 'admin_bank_wallet');
        yield MenuItem::linktoRoute('Notifications en échec', 'fas fa-bell-slash', 'admin_failed_notifications');
        yield MenuItem::linkToCrud('Economy Settings', 'fas fa-sliders-h', EconomySettings::class);

        yield MenuItem::section('Extra');
        // Todo set link here
        yield MenuItem::linkToUrl('YTCG - Admin', 'fa-brands fa-wizards-of-the-coast', 'https://google.com');
        yield MenuItem::linkToUrl('YC Seasons - Admin', 'fas fa-calendar-days', $this->adminUrls['seasons'] ?? 'https://google.com');
    }
}
