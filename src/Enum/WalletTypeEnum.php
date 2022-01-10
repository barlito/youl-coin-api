<?php

declare(strict_types=1);

namespace App\Enum;

class WalletTypeEnum
{
    public const USER = 'user';
    public const PREDICTION = 'prediction';

    public const VALUES = [
        1 => self::USER,
        2 => self::PREDICTION,
    ];

    public static function getValuesForEasyAdmin(): array
    {
        return [
            self::USER => self::USER,
            self::PREDICTION => self::PREDICTION,
        ];
    }
}
