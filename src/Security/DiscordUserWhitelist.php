<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\AllowedDiscordUserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Tells whether a Discord account may access the app.
 *
 * The whitelist is the union of two sources:
 *  - the "app.allowed_discord_users" parameter, a static bootstrap list that cannot be
 *    edited at runtime and therefore guarantees nobody can lock everyone out;
 *  - the AllowedDiscordUser table, editable from the admin.
 */
readonly class DiscordUserWhitelist
{
    /**
     * @var list<string>
     */
    private array $bootstrapDiscordIds;

    /**
     * @param list<int|string> $bootstrapDiscordIds
     */
    public function __construct(
        #[Autowire(param: 'app.allowed_discord_users')]
        array $bootstrapDiscordIds,
        private AllowedDiscordUserRepository $allowedDiscordUserRepository,
    ) {
        $this->bootstrapDiscordIds = array_map(strval(...), $bootstrapDiscordIds);
    }

    public function isAllowed(string $discordId): bool
    {
        if (\in_array($discordId, $this->bootstrapDiscordIds, true)) {
            return true;
        }

        return $this->allowedDiscordUserRepository->existsByDiscordId($discordId);
    }
}
