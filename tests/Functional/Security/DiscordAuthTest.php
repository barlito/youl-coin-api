<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordAuthTest extends WebTestCase
{
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    private const string DEFAULT_WELCOME_BONUS_AMOUNT = '100000000000';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
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
        $this->assertSame(self::DEFAULT_WELCOME_BONUS_AMOUNT, $wallet->getAmount());
        $this->assertSame(WalletTypeEnum::USER, $wallet->getType());

        $this->assertSame('900000000000', $this->fetchAmount(self::BANK_WALLET_ID));
        $this->assertWelcomeBonusTransactionCount($wallet, 1);
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
        $this->assertSame(self::DEFAULT_WELCOME_BONUS_AMOUNT, $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 1);
    }

    // Fixture wallets are freshly created by the fixture reload, so on their own they would be bonus-eligible;
    // this backdates Farph's wallet past the 30-day window to also cover "old wallet gets nothing".
    public function testLoginKeepsTheExistingWalletAndGrantsNoBonusToAnOldWallet(): void
    {
        $userId = '188967949963362304';

        $this->ageWallet('01FPD1DRHVBMZEM5EGS95F5N3E', '-40 days');

        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $userId, 'username' => 'Farph']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('01FPD1DRHVBMZEM5EGS95F5N3E', (string) $wallet->getId());
        $this->assertSame('700000000000', $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 0);
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

    public function testWelcomeBonusIsNotGrantedTwiceOnASecondLogin(): void
    {
        $userId = '189029821328785409';
        $this->removeUser($userId);

        $discordResource = new DiscordResourceOwner(['id' => $userId, 'username' => 'Veli']);

        $this->mockClientRegistry($discordResource);
        $this->client->request('GET', '/connect/discord/check');
        self::assertResponseRedirects('/');

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame(self::DEFAULT_WELCOME_BONUS_AMOUNT, $wallet->getAmount());

        // A real re-login boots a fresh kernel: start a new client and mock its own ClientRegistry
        $this->restartClient();
        $this->mockClientRegistry($discordResource);
        $this->client->request('GET', '/connect/discord/check');
        self::assertResponseRedirects('/');

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame(self::DEFAULT_WELCOME_BONUS_AMOUNT, $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 1);
    }

    // The key guarantee: an empty bank must never turn the login itself into a failure
    public function testLoginSucceedsWhenTheBankIsEmptyThenGrantsTheBonusOnceRefilled(): void
    {
        $userId = '189029821328785409';
        $this->removeUser($userId);

        $this->setWalletAmount(self::BANK_WALLET_ID, '0');

        $discordResource = new DiscordResourceOwner(['id' => $userId, 'username' => 'Veli']);
        $this->mockClientRegistry($discordResource);

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('0', $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 0);

        $this->setWalletAmount(self::BANK_WALLET_ID, '1000000000000');

        $this->restartClient();
        $this->mockClientRegistry($discordResource);
        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame(self::DEFAULT_WELCOME_BONUS_AMOUNT, $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 1);
    }

    public function testNoBonusIsGrantedWhenTheConfiguredAmountIsZero(): void
    {
        $userId = '189029821328785409';
        $this->removeUser($userId);

        $settings = $this->entityManager->find(EconomySettings::class, EconomySettings::SINGLETON_ID);
        $this->assertInstanceOf(EconomySettings::class, $settings);
        $settings->setWelcomeBonusAmountCoins(0);
        $this->entityManager->flush();

        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $userId, 'username' => 'Veli']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');

        $wallet = $this->findWallet($userId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('0', $wallet->getAmount());
        $this->assertWelcomeBonusTransactionCount($wallet, 0);
    }

    // A real second login boots a fresh kernel: simulate that so the mocked ClientRegistry isn't
    // rejected as "already initialized" by the previous request's container.
    private function restartClient(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function findWallet(string $discordId): ?Wallet
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(Wallet::class)->findOneBy(['discordUser' => $discordId]);
    }

    private function assertWelcomeBonusTransactionCount(Wallet $wallet, int $expectedCount): void
    {
        $this->entityManager->clear();

        $transactions = $this->entityManager->getRepository(Transaction::class)->findBy([
            'walletTo' => $wallet,
            'type' => TransactionTypeEnum::WELCOME_BONUS,
        ]);

        $this->assertCount($expectedCount, $transactions);
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

    private function ageWallet(string $walletId, string $modifier): void
    {
        $wallet = $this->entityManager->find(Wallet::class, $walletId);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $wallet->setCreatedAt(new \DateTime($modifier));
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function setWalletAmount(string $walletId, string $amount): void
    {
        $this->connection()->executeStatement(
            'UPDATE wallet SET amount = :amount WHERE id = :id',
            ['amount' => $amount, 'id' => Ulid::fromString($walletId)->toRfc4122()],
        );
    }

    private function fetchAmount(string $walletId): string
    {
        return (string) $this->connection()->fetchOne(
            'SELECT amount FROM wallet WHERE id = :id',
            ['id' => Ulid::fromString($walletId)->toRfc4122()],
        );
    }

    private function connection(): Connection
    {
        return $this->entityManager->getConnection();
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

        static::getContainer()->set(ClientRegistry::class, $mockClientRegistry);
    }
}
