<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Login;

use App\Entity\AllowedDiscordUser;
use App\Entity\DiscordUser;
use App\Repository\DiscordUserRepository;
use App\Security\WhitelistUserChecker;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class RefreshTokenSecurityTest extends WebTestCase
{
    private KernelBrowser $client;
    private ContainerInterface $container;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->container = static::getContainer();
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
        $this->assertStringContainsString(WhitelistUserChecker::ACCESS_DENIED_MESSAGE, (string) $this->client->getResponse()->getContent());
        $this->assertNull($this->client->getCookieJar()->get('jwt'));
        $this->assertNull($this->client->getCookieJar()->get('REMEMBERME'));
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
