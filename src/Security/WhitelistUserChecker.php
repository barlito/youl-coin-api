<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\DiscordUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Re-checks the Discord whitelist on every authentication on the "main" firewall, not only
 * at login: this also runs when a remember-me cookie silently re-authenticates a request, so
 * an account removed from the whitelist loses access even while its remember-me cookie (1 week)
 * is still valid.
 */
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
