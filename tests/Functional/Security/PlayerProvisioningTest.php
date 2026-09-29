<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\AllowedDiscordUser;
use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Enum\TransactionTypeEnum;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Ulid;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class PlayerProvisioningTest extends WebTestCase
{
    use MocksDiscordOAuthTrait;

    private const string DISCORD_ID = '297453953120075778';
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->boot();
    }

    public function testDiscordLoginOfANewPlayerCreatesTheWalletAndTheBonus(): void
    {
        $this->mockClientRegistry(new DiscordResourceOwner(['id' => self::DISCORD_ID, 'username' => 'Dynamo']));

        $this->client->request('GET', '/connect/discord/check');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');
        $this->assertBonusCount(1);
    }

    public function testRememberMeAloneProvisionsAPlayerWithoutWallet(): void
    {
        $this->setBankAmount('0');
        $this->mockClientRegistry(new DiscordResourceOwner(['id' => self::DISCORD_ID, 'username' => 'Dynamo']));
        $this->client->request('GET', '/connect/discord/check');
        $rememberMeCookie = $this->extractCookie('REMEMBERME');
        $this->assertInstanceOf(Cookie::class, $rememberMeCookie);

        $this->connection()->executeStatement('DELETE FROM wallet WHERE discord_user_id = :id', ['id' => self::DISCORD_ID]);
        $this->setBankAmount('1000000000000');
        $this->assertNull($this->findWallet());

        $this->boot();
        $this->client->getCookieJar()->set($this->toBrowserKitCookie($rememberMeCookie));
        $this->client->request('GET', '/');

        $this->assertBonusCount(1);
    }

    public function testRefreshTokenProvisionsAPlayerInSessionOnceOnly(): void
    {
        $this->client->loginUser($this->createPlayer());

        $this->client->request('GET', '/refresh_token');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');
        $this->assertBonusCount(1);

        $this->client->request('GET', '/refresh_token');

        self::assertResponseRedirects('/');
        $this->assertBonusCount(1);
    }

    public function testRefreshTokenSucceedsWithAnEmptyBankThenCatchesUpOnceRefilled(): void
    {
        $this->setBankAmount('0');
        $this->client->loginUser($this->createPlayer());

        $this->client->request('GET', '/refresh_token');

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');
        $this->assertInstanceOf(Wallet::class, $this->findWallet());
        $this->assertBonusCount(0);

        $this->setBankAmount('1000000000000');
        $this->boot();
        $this->client->loginUser($this->entityManager->getRepository(DiscordUser::class)->find(self::DISCORD_ID));
        $this->client->request('GET', '/refresh_token');

        self::assertResponseRedirects('/');
        $this->assertBonusCount(1);
    }

    public function testAPlayerRemovedFromTheWhitelistIsRefusedAndNothingIsCreated(): void
    {
        $user = $this->createPlayer();
        $this->entityManager->remove($this->entityManager->getRepository(AllowedDiscordUser::class)->find(self::DISCORD_ID));
        $this->entityManager->flush();
        $this->client->loginUser($user);

        $this->client->request('GET', '/refresh_token');

        self::assertResponseStatusCodeSame(403);
        $this->assertNull($this->findWallet());
    }

    private function boot(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createPlayer(): DiscordUser
    {
        $user = new DiscordUser()
            ->setDiscordId(self::DISCORD_ID)
            ->setUsername('Dynamo')
            ->setRoles([RoleEnum::ROLE_USER->value])
        ;
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function findWallet(): ?Wallet
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(Wallet::class)->findOneBy(['discordUser' => self::DISCORD_ID]);
    }

    private function assertBonusCount(int $expected): void
    {
        $wallet = $this->findWallet();
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertCount($expected, $this->entityManager->getRepository(Transaction::class)->findBy([
            'walletTo' => $wallet,
            'type' => TransactionTypeEnum::WELCOME_BONUS,
        ]));
    }

    private function setBankAmount(string $amount): void
    {
        $this->connection()->executeStatement(
            'UPDATE wallet SET amount = :amount WHERE id = :id',
            ['amount' => $amount, 'id' => Ulid::fromString(self::BANK_WALLET_ID)->toRfc4122()],
        );
    }

    private function connection(): Connection
    {
        return $this->entityManager->getConnection();
    }

    private function extractCookie(string $name): ?Cookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($name === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    private function toBrowserKitCookie(Cookie $cookie): BrowserKitCookie
    {
        return new BrowserKitCookie(
            $cookie->getName(),
            $cookie->getValue(),
            null === $cookie->getExpiresTime() ? null : (string) $cookie->getExpiresTime(),
            $cookie->getPath(),
            $cookie->getDomain() ?? 'localhost',
            $cookie->isSecure(),
            $cookie->isHttpOnly(),
            false,
            $cookie->getSameSite(),
        );
    }
}
