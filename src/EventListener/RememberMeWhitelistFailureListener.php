<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * A remember-me cookie (1 week) can silently re-authenticate a request for an account that was
 * since removed from the whitelist (WhitelistUserChecker throws in that case). Without this
 * listener the request would just fall back to anonymous; here it fails closed instead: 403,
 * session invalidated, remember-me and jwt cookies cleared. Covers every route on the "main"
 * firewall, not only /refresh_token, since it runs before the controller (kernel.request).
 */
#[AsEventListener(event: LoginFailureEvent::class)]
readonly class RememberMeWhitelistFailureListener
{
    public function __construct(
        #[Autowire(env: 'JWT_COOKIE_DOMAIN')]
        private string $jwtCookieDomain,
    ) {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        if (!$event->getAuthenticator() instanceof RememberMeAuthenticator || !$exception instanceof CustomUserMessageAccountStatusException) {
            return;
        }

        $event->getRequest()->getSession()->invalidate();

        $response = new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        $response->headers->clearCookie('REMEMBERME', '/');
        $response->headers->clearCookie('jwt', '/', $this->jwtCookieDomain);

        $event->setResponse($response);
    }
}
