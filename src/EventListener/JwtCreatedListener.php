<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\DiscordUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created', method: 'onJwtCreated')]
readonly class JwtCreatedListener
{
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        $payload = $event->getData();

        if ($user instanceof DiscordUser) {
            $payload['discordId'] = $user->getDiscordId();
        }

        $event->setData($payload);
    }
}
