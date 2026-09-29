<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\DiscordUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Also runs on remember-me re-authentication, not only at the Discord login
readonly class WhitelistUserChecker implements UserCheckerInterface
{
    public const string ACCESS_DENIED_MESSAGE = 'Your account is not allowed to access this app.';

    public function __construct(
        private DiscordUserWhitelist $discordUserWhitelist,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof DiscordUser && !$this->discordUserWhitelist->isAllowed($user->getDiscordId())) {
            throw new CustomUserMessageAccountStatusException(self::ACCESS_DENIED_MESSAGE);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
