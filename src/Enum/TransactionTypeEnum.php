<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case CLASSIC = 'classic';
    case AIR_DROP = 'air_drop';
    case REGULATION = 'regulation';
    case SEASON_REWARD = 'season_reward';

    public const VALUES = [
        self::CLASSIC->name => self::CLASSIC->value,
        self::AIR_DROP->name => self::AIR_DROP->value,
        self::REGULATION->name => self::REGULATION->value,
        self::SEASON_REWARD->name => self::SEASON_REWARD->value,
    ];
}
