<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\DiscordUser;
use App\Security\Exception\DiscordUserNotAllowedException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Also runs on remember-me re-authentication, not only at the Discord login
readonly class WhitelistUserChecker implements UserCheckerInterface
{
    public function __construct(
        private DiscordUserWhitelist $discordUserWhitelist,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof DiscordUser && !$this->discordUserWhitelist->isAllowed($user->getDiscordId())) {
            throw new DiscordUserNotAllowedException();
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
