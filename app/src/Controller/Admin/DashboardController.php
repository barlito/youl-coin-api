<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Wallet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly array $adminUrls,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->redirect($this->adminUrlGenerator->setController(WalletCrudController::class)->generateUrl());

        // return some charts of week transactions something like that
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('YoulCoin Exchange Admin')
            ->setFaviconPath('https://www.creativefabrica.com/wp-content/uploads/2019/03/Monogram-YC-Logo-Design-by-Greenlines-Studios.jpg')
            ->renderContentMaximized()
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Wallet Settings');
        yield MenuItem::linkToCrud('Wallets', 'fas fa-wallet', Wallet::class);
        //        second wallet custom page for bank wallet transactions
        //        yield MenuItem::linkToCrud('Wallets', 'fas fa-calendar-days', Wallet::class);

        yield MenuItem::section('Extra');
        // Todo set link here
        yield MenuItem::linkToUrl('YTCG - Admin', 'fa-brands fa-wizards-of-the-coast', 'https://google.com');
        yield MenuItem::linkToUrl('YC Seasons - Admin', 'fas fa-calendar-days', $this->adminUrls['seasons'] ?? 'https://google.com');
    }
}
