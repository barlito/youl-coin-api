<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\Exception\DiscordUserNotAllowedException;
use App\Security\NotAllowedResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

// Covers the Discord login and remember-me: the 403 also clears the jwt cookie
#[AsEventListener(event: LoginFailureEvent::class)]
readonly class NotAllowedLoginFailureListener
{
    public function __construct(private NotAllowedResponseFactory $notAllowedResponseFactory)
    {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        if ($event->getException() instanceof DiscordUserNotAllowedException) {
            $event->setResponse($this->notAllowedResponseFactory->create());
        }
    }
}
