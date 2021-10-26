<?php

declare(strict_types=1);

namespace App\Money;

final class YoulCoinFormatter
{
    public static function format(string $amount): string
    {
        return YoulCoinCurrency::SYMBOL.$amount;
    }
}
