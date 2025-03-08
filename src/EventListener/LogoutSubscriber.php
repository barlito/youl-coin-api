<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'JWT_COOKIE_DOMAIN')]
        private string $cookieDomain,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof Response) {
            $response = new RedirectResponse(
                $this->urlGenerator->generate('homepage'),
                Response::HTTP_SEE_OTHER,
            );
        }

        $response->headers->clearCookie('jwt', domain: $this->cookieDomain);
        $event->setResponse($response);
    }
}
