<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\DiscordUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordAuthTest extends WebTestCase
{
    private KernelBrowser $client;
    private ContainerInterface $container;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessfulLoginWithWhitelistedUser(): void
    {
        $discordUserRepository = $this->entityManager->getRepository(DiscordUser::class);

        $userId = '189029821328785409';
        $this->removeUser($userId);

        $discordResource = (new DiscordResourceOwner([
            'id' => $userId,
            'username' => 'Veli',
        ]));

        $this->mockClientRegistry($discordResource);

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');

        $user = $discordUserRepository->findOneBy(['discordId' => $discordResource->getId()]);
        $this->assertNotNull($user);
        $this->assertInstanceOf(DiscordUser::class, $user);
    }

    public function testLoginFailsForNonWhitelistedUser(): void
    {
        $discordUserRepository = $this->entityManager->getRepository(DiscordUser::class);

        $nonWhitelistedUserId = '999999999999999999';
        $discordResource = new DiscordResourceOwner([
            'id' => $nonWhitelistedUserId,
            'username' => 'RandomBud',
        ]);

        $this->mockClientRegistry($discordResource);

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseStatusCodeSame(403);

        $user = $discordUserRepository->findOneBy(['discordId' => $nonWhitelistedUserId]);
        $this->assertNull($user);
    }

    private function removeUser(string $userId): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $discordUserRepository = $entityManager->getRepository(DiscordUser::class);

        /** @var DiscordUser $userToRemove */
        $userToRemove = $discordUserRepository->findOneBy(['discordId' => $userId]);
        $entityManager->remove($userToRemove->getWallet());
        $entityManager->remove($userToRemove);
        $entityManager->flush();
    }

    private function mockClientRegistry(DiscordResourceOwner $discordResource): void
    {
        $mockAccessToken = $this->createMock(AccessToken::class);
        $mockAccessToken->method('getToken')->willReturn('fake_access_token');

        $mockOAuthClient = $this->createMock(OAuth2Client::class);
        $mockOAuthClient->method('getAccessToken')->willReturn($mockAccessToken);
        $mockOAuthClient->method('fetchUserFromToken')->willReturn($discordResource);

        $mockClientRegistry = $this->createMock(ClientRegistry::class);
        $mockClientRegistry->method('getClient')->with('discord')->willReturn($mockOAuthClient);

        $this->container->set(ClientRegistry::class, $mockClientRegistry);
    }
}
