<?php

declare(strict_types=1);

namespace App\Enum;

class TransactionTypeEnum
{
    public const CLASSIC = 'classic';
    public const AIR_DROP = 'air_drop';

    public const VALUES = [
        1 => self::CLASSIC,
        2 => self::AIR_DROP,
    ];
}
