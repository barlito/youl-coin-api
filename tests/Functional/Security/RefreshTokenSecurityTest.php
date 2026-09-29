<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\AllowedDiscordUser;
use App\Entity\DiscordUser;
use App\Enum\Roles\RoleEnum;
use App\Repository\DiscordUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Cookie;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class RefreshTokenSecurityTest extends WebTestCase
{
    use MocksDiscordOAuthTrait;

    private const string JWT_COOKIE_DOMAIN = '.youlz.fr';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRefreshTokenRedirectsToAllowedRelativeTarget(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('GET', '/refresh_token?_target_path=%2Fmon-espace');

        self::assertResponseRedirects('/mon-espace');
        self::assertBrowserHasCookie('jwt');
    }

    public function testRefreshTokenRedirectsToAllowedAbsoluteTarget(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('GET', '/refresh_token?_target_path=' . urlencode('https://ytcg.youlz.fr/collection'));

        self::assertResponseRedirects('https://ytcg.youlz.fr/collection');
        self::assertBrowserHasCookie('jwt');
    }

    public function testRefreshTokenIgnoresAbsoluteOpenRedirectAndFallsBackToHomepage(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('GET', '/refresh_token?_target_path=' . urlencode('https://evil.example'));

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('jwt');
    }

    public function testRefreshTokenIgnoresProtocolRelativeOpenRedirectAndFallsBackToHomepage(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('GET', '/refresh_token?_target_path=' . urlencode('//evil.example'));

        self::assertResponseRedirects('/');
    }

    public function testRefreshTokenDeniesAndDeauthenticatesAUserRemovedFromTheWhitelistViaRememberMe(): void
    {
        $discordId = '297453953120075778';
        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $discordId, 'username' => 'Dynamo']));

        // Real OAuth login, so Symfony issues a genuine remember-me cookie (always_remember_me: true)
        $this->client->request('GET', '/connect/discord/check');
        self::assertResponseRedirects('/');

        $rememberMeCookie = $this->extractCookie($this->client, 'REMEMBERME');
        $this->assertInstanceOf(Cookie::class, $rememberMeCookie);

        $this->revokeFromWhitelist($discordId);

        // Simulate a fresh browser: drop the PHP session, keep only the remember-me cookie
        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set($this->toBrowserKitCookie($rememberMeCookie));

        $this->client->request('GET', '/refresh_token');

        self::assertResponseStatusCodeSame(403);
        $this->assertStringContainsString('not allowed', (string) $this->client->getResponse()->getContent());
        $this->assertJwtCookieCleared();
        $this->assertTrue($this->extractCookie($this->client, 'REMEMBERME')?->isCleared());
    }

    public function testRefreshTokenDeniesAndDeauthenticatesAnActiveSessionOfAUserRemovedFromTheWhitelist(): void
    {
        $discordId = '297453953120075778';
        $user = $this->entityManager->getRepository(DiscordUser::class)->find($discordId)
            ?? $this->createUser($discordId);
        $this->client->loginUser($user);

        $this->revokeFromWhitelist($discordId);

        $this->client->request('GET', '/refresh_token');

        self::assertResponseStatusCodeSame(403);
        $this->assertJwtCookieCleared();
        $this->assertNull($this->extractCookie($this->client, 'jwt')?->getValue());

        $this->client->request('GET', '/refresh_token');
        self::assertResponseRedirects('/connect/discord');
    }

    public function testRefreshTokenStillWorksViaRememberMeForAWhitelistedUser(): void
    {
        $admin = $this->getAdminUser();
        $this->mockClientRegistry(new DiscordResourceOwner(['id' => $admin->getDiscordId(), 'username' => $admin->getUsername()]));

        $this->client->request('GET', '/connect/discord/check');
        self::assertResponseRedirects('/');

        $rememberMeCookie = $this->extractCookie($this->client, 'REMEMBERME');
        $this->assertInstanceOf(Cookie::class, $rememberMeCookie);

        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set($this->toBrowserKitCookie($rememberMeCookie));

        $this->client->request('GET', '/refresh_token');

        self::assertResponseRedirects('/');
        $this->assertNotNull($this->client->getCookieJar()->get('jwt'));
    }

    private function assertJwtCookieCleared(): void
    {
        $cookie = $this->extractCookie($this->client, 'jwt');

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertTrue($cookie->isCleared());
        $this->assertSame(self::JWT_COOKIE_DOMAIN, $cookie->getDomain());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    private function createUser(string $discordId): DiscordUser
    {
        $user = new DiscordUser()
            ->setDiscordId($discordId)
            ->setUsername('Dynamo')
            ->setRoles([RoleEnum::ROLE_USER->value])
        ;
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function extractCookie(KernelBrowser $client, string $name): ?Cookie
    {
        $cookies = $client->getResponse()->headers->getCookies();

        foreach ($cookies as $cookie) {
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

    private function revokeFromWhitelist(string $discordId): void
    {
        $repository = $this->entityManager->getRepository(AllowedDiscordUser::class);
        $entry = $repository->find($discordId);

        if (null !== $entry) {
            $this->entityManager->remove($entry);
            $this->entityManager->flush();
        }
    }

    private function getAdminUser(): DiscordUser
    {
        /** @var DiscordUserRepository $discordUserRepository */
        $discordUserRepository = static::getContainer()->get(DiscordUserRepository::class);
        $user = $discordUserRepository->findOneBy(['discordId' => '188967649332428800']);

        if (null === $user) {
            throw new \RuntimeException('User not found');
        }

        return $user;
    }
}
