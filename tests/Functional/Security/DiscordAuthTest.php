<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Enum\WalletTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordAuthTest extends WebTestCase
{
    use MocksDiscordOAuthTrait;

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

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('0', $wallet->getAmount());
        $this->assertSame(WalletTypeEnum::USER, $wallet->getType());
    }

    public function testLoginCreatesTheMissingWalletOfAnExistingUser(): void
    {
        // Whitelisted dynamically, account created before wallets were provisioned at login
        $userId = '297453953120075778';
        $this->entityManager->persist(
            new DiscordUser()
                ->setDiscordId($userId)
                ->setUsername('Dynamo')
                ->setRoles([RoleEnum::ROLE_USER->value]),
        );
        $this->entityManager->flush();
        // Hydrate the user from the database during the login, as in production
        $this->entityManager->clear();

        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $userId, 'username' => 'Dynamo']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('0', $wallet->getAmount());
    }

    public function testLoginKeepsTheExistingWallet(): void
    {
        $userId = '188967949963362304';

        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $userId, 'username' => 'Farph']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('01FPD1DRHVBMZEM5EGS95F5N3E', (string) $wallet->getId());
        $this->assertSame('700000000000', $wallet->getAmount());
    }

    public function testSuccessfulLoginWithDynamicallyWhitelistedUser(): void
    {
        $discordUserRepository = $this->entityManager->getRepository(DiscordUser::class);

        // Only whitelisted through the AllowedDiscordUser table, not through the bootstrap parameter.
        $userId = '297453953120075778';

        $discordResource = new DiscordResourceOwner([
            'id' => $userId,
            'username' => 'Dynamo',
        ]);

        $this->mockClientRegistry($discordResource);

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');

        $this->assertInstanceOf(DiscordUser::class, $discordUserRepository->findOneBy(['discordId' => $userId]));
    }

    public function testLoginFailsForExistingUserRemovedFromWhitelist(): void
    {
        $userId = '111111111111111111';
        $this->entityManager->persist(
            new DiscordUser()
                ->setDiscordId($userId)
                ->setUsername('Revoked')
                ->setRoles([RoleEnum::ROLE_USER->value]),
        );
        $this->entityManager->flush();

        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $userId, 'username' => 'Revoked']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseStatusCodeSame(403);
        self::assertBrowserNotHasCookie('jwt');
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
        $this->assertNull($this->findWallet($nonWhitelistedUserId));
    }

    private function findWallet(string $discordId): ?Wallet
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(Wallet::class)->findOneBy(['discordUser' => $discordId]);
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
}
