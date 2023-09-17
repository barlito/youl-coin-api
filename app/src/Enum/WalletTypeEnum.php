<?php

declare(strict_types=1);

namespace App\Enum;

class WalletTypeEnum
{
    public const USER = 'user';
    public const BANK = 'bank';

    public const VALUES = [
        1 => self::USER,
        2 => self::BANK,
    ];

    public static function getValuesForEasyAdmin(): array
    {
        return [
            self::USER => self::USER,
            self::BANK => self::BANK,
        ];
    }
}
