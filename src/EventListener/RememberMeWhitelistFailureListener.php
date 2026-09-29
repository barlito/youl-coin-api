<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

// Fails closed (403, session and cookies cleared) when a remember-me cookie outlives the whitelist entry
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
