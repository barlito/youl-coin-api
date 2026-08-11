<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\AllowedDiscordUserCrudController;
use App\Controller\Admin\DashboardController;
use App\Repository\DiscordUserRepository;
use App\Security\DiscordUserWhitelist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class AllowedDiscordUserCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->client->loginUser($this->getAdminUser());
    }

    public function testIndexListsTheWhitelistedUsers(): void
    {
        $this->client->request('GET', $this->adminUrl(Action::INDEX));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '297453953120075778');
    }

    public function testAnAdminCanWhitelistANewDiscordUser(): void
    {
        $discordId = '123456789012345678';

        $crawler = $this->client->request('GET', $this->adminUrl(Action::NEW));
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="AllowedDiscordUser"]')->form([
            'AllowedDiscordUser[discordId]' => $discordId,
            'AllowedDiscordUser[label]' => 'Newcomer',
        ]));

        self::assertResponseRedirects();

        $whitelist = static::getContainer()->get(DiscordUserWhitelist::class);
        $this->assertTrue($whitelist->isAllowed($discordId));
    }

    public function testAnInvalidDiscordIdIsRejected(): void
    {
        $crawler = $this->client->request('GET', $this->adminUrl(Action::NEW));

        $this->client->submit($crawler->filter('form[name="AllowedDiscordUser"]')->form([
            'AllowedDiscordUser[discordId]' => 'not-a-snowflake',
        ]));

        self::assertResponseStatusCodeSame(422);

        $whitelist = static::getContainer()->get(DiscordUserWhitelist::class);
        $this->assertFalse($whitelist->isAllowed('not-a-snowflake'));
    }

    private function adminUrl(string $action): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(AllowedDiscordUserCrudController::class)
            ->setAction($action)
            ->generateUrl()
        ;
    }

    private function getAdminUser(): UserInterface
    {
        /** @var DiscordUserRepository $discordUserRepository */
        $discordUserRepository = static::getContainer()->get(DiscordUserRepository::class);
        $user = $discordUserRepository->findOneBy(['discordId' => '188967649332428800']);

        if (null === $user) {
            throw new \RuntimeException('Admin user not found');
        }

        return $user;
    }
}
