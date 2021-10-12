<?php

namespace App\Controller\Admin;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkToCrud('Discord Users', 'fas fa-list', DiscordUser::class);

        yield MenuItem::linkToCrud('Transactions', 'fas fa-list', Transaction::class);

        yield MenuItem::linkToCrud('Wallets', 'fas fa-list', Wallet::class);
    }
}
