<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Login;

use App\Repository\DiscordUserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

class DiscordAuthTest extends WebTestCase
{
    public function testJwtIsSetOnRefreshToken(): void
    {
        $client = $this->getCustomClient();

        $client->loginUser($this->getAdminUser());

        self::assertBrowserNotHasCookie('jwt', '/', $_ENV['JWT_COOKIE_DOMAIN']);

        $client->request('GET', '/refresh_token');
        self::assertResponseRedirects();

        $cookies = $client->getResponse()->headers->getCookies();
        $jwtCookie = current(array_filter($cookies, fn (Cookie $cookie) => 'jwt' === $cookie->getName()));

        $this->assertInstanceOf(Cookie::class, $jwtCookie);
        $this->assertTrue($jwtCookie->isHttpOnly());
        $this->assertTrue($jwtCookie->isSecure());
        $this->assertSame('lax', $jwtCookie->getSameSite());
        $this->assertSame($_ENV['JWT_COOKIE_DOMAIN'], $jwtCookie->getDomain());
        $this->assertSame('/', $jwtCookie->getPath());
        $this->assertNotNull($jwtCookie->getExpiresTime());

        self::assertResponseHasCookie('jwt', '/', $_ENV['JWT_COOKIE_DOMAIN']);
        self::assertBrowserHasCookie('jwt', '/', $_ENV['JWT_COOKIE_DOMAIN']);
    }

    public function testJwtIsRemovedOnLogout(): void
    {
        $client = $this->getCustomClient();

        $user = $this->getAdminUser();
        $client->loginUser($user);
        $cookie = $this->getCookie($user);
        $client->getCookieJar()->set($cookie);

        $client->request('GET', '/');
        self::assertBrowserHasCookie('jwt', '/', $_ENV['JWT_COOKIE_DOMAIN']);

        $client->request('GET', '/logout');

        self::assertResponseRedirects();
        self::assertBrowserNotHasCookie('jwt', '/', $_ENV['JWT_COOKIE_DOMAIN']);

        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $jwt = $jwtManager->parse($cookie->getValue());
        $this->assertNotNull($jwt['jti']);
        $cacheItem = static::getContainer()->get('cache.app')->getItem($jwt['jti']);
        $this->assertTrue($cacheItem->isHit());
    }

    private function getCustomClient(): KernelBrowser
    {
        return static::createClient(server: ['HTTP_HOST' => 'test' . $_ENV['JWT_COOKIE_DOMAIN'], 'HTTPS' => 'on']);
    }

    private function getAdminUser(): UserInterface
    {
        /** @var DiscordUserRepository $discordUserRepository */
        $discordUserRepository = static::getContainer()->get(DiscordUserRepository::class);
        $user = $discordUserRepository->findOneBy(['discordId' => '188967649332428800']);

        if (null === $user) {
            throw new \RuntimeException('User not found');
        }

        return $user;
    }

    private function getCookie(UserInterface $user): \Symfony\Component\BrowserKit\Cookie
    {
        $cookie = $this->generateCookie($user);

        return new \Symfony\Component\BrowserKit\Cookie(
            $cookie->getName(),
            $cookie->getValue(),
            (string) $cookie->getExpiresTime(),
            $cookie->getPath(),
            $cookie->getDomain(),
            $cookie->isSecure(),
            $cookie->isHttpOnly(),
            false,
            $cookie->getSameSite(),
        );
    }

    public function generateCookie(?UserInterface $user): Cookie
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        if (null === $user) {
            throw new \InvalidArgumentException('User cannot be null, this should not happen.');
        }

        $jwtToken = $jwtManager->create($user);

        return Cookie::create('jwt', $jwtToken, time() + 900, '/', $_ENV['JWT_COOKIE_DOMAIN'], true, true, sameSite: Cookie::SAMESITE_LAX);
    }
}
