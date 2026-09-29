<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\ApiUserCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\ApiUser;
use App\Entity\DiscordUser;
use App\Enum\Roles\ApiUserRoleEnum;
use App\Repository\ApiUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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

    public function testTheKeyOfANewApiClientIsShownOnceAndAuthenticates(): void
    {
        $client = $this->loggedInAdminClient();

        $crawler = $client->request('GET', $this->crudUrl(Action::NEW));
        $client->submit($crawler->selectButton('Create')->form([
            'ApiUser[name]' => 'new_client',
            'ApiUser[roles]' => [ApiUserRoleEnum::ROLE_WALLET_READ->value],
        ]));
        self::assertResponseRedirects();

        $plainApiKey = $this->extractFlashedKey($client);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $plainApiKey);

        $apiUser = static::getContainer()->get(ApiUserRepository::class)->findOneBy(['name' => 'new_client']);
        $this->assertInstanceOf(ApiUser::class, $apiUser);
        $this->assertSame(substr($plainApiKey, 0, 6), $apiUser->getApiKeyPrefix());
        $this->assertContains(ApiUserRoleEnum::ROLE_WALLET_READ->value, $apiUser->getRoles());

        $client->request('GET', $this->crudUrl(Action::INDEX));
        self::assertSelectorTextNotContains('body', $plainApiKey);

        $this->assertSame(200, $this->statusWithKey($client, $plainApiKey));
    }

    public function testRegeneratingTheKeyInvalidatesTheOldOne(): void
    {
        $client = $this->loggedInAdminClient();

        $client->request('POST', $this->crudUrl('regenerateKey', $this->apiUserId('reader')));
        self::assertResponseRedirects();

        $plainApiKey = $this->extractFlashedKey($client);

        $this->assertSame(401, $this->statusWithKey($client, 'api_key_reader'));
        $this->assertSame(200, $this->statusWithKey($client, $plainApiKey));
    }

    public function testTheRolesOfAnApiClientStayEditableWithoutAnyKeyField(): void
    {
        $client = $this->loggedInAdminClient();

        $crawler = $client->request('GET', $this->crudUrl(Action::EDIT, $this->apiUserId('reader')));
        self::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Save changes')->form(['ApiUser[roles]' => [ApiUserRoleEnum::ROLE_TRANSACTION_READ->value]]));
        self::assertResponseRedirects();

        $apiUser = static::getContainer()->get(ApiUserRepository::class)->findOneBy(['name' => 'reader']);
        $this->assertInstanceOf(ApiUser::class, $apiUser);
        $this->assertContains(ApiUserRoleEnum::ROLE_TRANSACTION_READ->value, $apiUser->getRoles());
        $this->assertSame('api_ke', $apiUser->getApiKeyPrefix());
    }

    private function loggedInAdminClient(): KernelBrowser
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $client = static::createClient();
        $admin = static::getContainer()->get(EntityManagerInterface::class)->find(DiscordUser::class, '188967649332428800');
        $this->assertInstanceOf(DiscordUser::class, $admin);
        $client->loginUser($admin);

        return $client;
    }

    private function crudUrl(string $action, ?string $entityId = null): string
    {
        $generator = static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(ApiUserCrudController::class)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $generator->setEntityId($entityId);
        }

        return $generator->generateUrl();
    }

    private function apiUserId(string $name): string
    {
        $apiUser = static::getContainer()->get(ApiUserRepository::class)->findOneBy(['name' => $name]);
        $this->assertInstanceOf(ApiUser::class, $apiUser);

        return (string) $apiUser->getId();
    }

    private function extractFlashedKey(KernelBrowser $client): string
    {
        $crawler = $client->followRedirect();
        $key = $crawler->filter('#flash-messages code')->text();

        $client->request('GET', $this->crudUrl(Action::INDEX));
        self::assertSelectorNotExists('#flash-messages');

        return $key;
    }

    private function statusWithKey(KernelBrowser $client, string $plainApiKey): int
    {
        $client->restart();
        $client->request('GET', '/api/user/232457563910832129/wallet', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $plainApiKey]);

        return $client->getResponse()->getStatusCode();
    }
}
