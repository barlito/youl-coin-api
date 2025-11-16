<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\DiscordUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: Events::JWT_CREATED, method: 'onJwtCreated')]
readonly class JwtCreatedListener
{
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        $payload = $event->getData();

        if ($user instanceof DiscordUser) {
            $payload['username'] = $user->getUsername();
        }

        $event->setData($payload);
    }
}
