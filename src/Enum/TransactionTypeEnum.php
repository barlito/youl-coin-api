<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case CLASSIC = 'classic';
    case AIR_DROP = 'air_drop';
    case REGULATION = 'regulation';
    case SEASON_REWARD = 'season_reward';
    // Admin-only: creates coins out of nowhere into the bank wallet
    case MINT = 'mint';
    // Admin-only: destroys coins held by the bank wallet
    case BURN = 'burn';

    public function isSupplyChange(): bool
    {
        return \in_array($this, [self::MINT, self::BURN], true);
    }
}
