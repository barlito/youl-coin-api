<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Repository\DiscordUserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
    }

    public function testTheAdminSeesTheEconomyDashboard(): void
    {
        $this->client->loginUser($this->getUser('188967649332428800'));

        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        foreach (['Masse monétaire', 'Concentration', 'Top 5 des soldes joueurs', 'par type', 'Volume par jour', 'Joueurs actifs', 'Wallet Barlito'] as $title) {
            self::assertSelectorTextContains('body', $title);
        }
        self::assertSelectorExists('.alert-danger');
        self::assertSelectorTextContains('.alert-danger', 'Registre incohérent');
        self::assertSelectorTextContains('a[href="/"]', 'Hub joueur');
        self::assertSelectorExists('a[href="https://ytcg.youlz.fr/admin"]');
    }

    public function testANonAdminIsDenied(): void
    {
        $this->client->loginUser($this->getUser('195659530363731968'));

        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    private function getUser(string $discordId): \Symfony\Component\Security\Core\User\UserInterface
    {
        $user = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => $discordId]);
        \assert(null !== $user);

        return $user;
    }
}
