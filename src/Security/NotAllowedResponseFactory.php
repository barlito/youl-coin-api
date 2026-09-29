<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Exception\DiscordUserNotAllowedException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

readonly class NotAllowedResponseFactory
{
    public function __construct(
        #[Autowire(env: 'JWT_COOKIE_DOMAIN')]
        private string $jwtCookieDomain,
    ) {
    }

    public function create(): Response
    {
        $response = new Response(new DiscordUserNotAllowedException()->getMessage(), Response::HTTP_FORBIDDEN);
        // Same attributes as lexik's set_cookies.jwt, or the browser keeps the cookie
        $response->headers->clearCookie('jwt', '/', $this->jwtCookieDomain, true, true, Cookie::SAMESITE_LAX);

        return $response;
    }
}
