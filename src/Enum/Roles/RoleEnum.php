<?php

declare(strict_types=1);

namespace App\Enum\Roles;

enum RoleEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_YTCG_ADMIN = 'ROLE_YTCG_ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::ROLE_USER => 'Joueur',
            self::ROLE_ADMIN => 'Admin Youl Coin',
            self::ROLE_YTCG_ADMIN => 'Admin Youl TCG',
        };
    }

    // ROLE_USER is implicit (DiscordUser::getRoles) so it is never granted by hand
    /**
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_YTCG_ADMIN];
    }
}
