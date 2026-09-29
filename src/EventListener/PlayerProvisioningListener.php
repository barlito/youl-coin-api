<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\DiscordUser;
use App\Service\Wallet\PlayerWalletProvisioner;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

// Covers the Discord login and remember-me; /refresh_token with a live session dispatches no login event
#[AsEventListener(event: LoginSuccessEvent::class)]
readonly class PlayerProvisioningListener
{
    public function __construct(private PlayerWalletProvisioner $playerWalletProvisioner)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ('main' === $event->getFirewallName() && $user instanceof DiscordUser) {
            $this->playerWalletProvisioner->provision($user);
        }
    }
}
