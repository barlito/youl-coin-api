<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\ApiUserCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\DiscordUser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiUserCrudControllerTest extends WebTestCase
{
    public function testAnApiClientCannotBeDeleted(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $client = static::createClient();
        $admin = static::getContainer()->get(EntityManagerInterface::class)->find(DiscordUser::class, '188967649332428800');
        $this->assertInstanceOf(DiscordUser::class, $admin);
        $client->loginUser($admin);

        $client->request('GET', static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(ApiUserCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'bank_only');
        self::assertSelectorNotExists('.action-delete');
    }
}
