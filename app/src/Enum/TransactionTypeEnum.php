<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case CLASSIC = 'classic';
    case AIR_DROP = 'air_drop';

    public const VALUES = [
        self::CLASSIC->name => self::CLASSIC->value,
        self::CLASSIC->name => self::AIR_DROP->value,
    ];
}
