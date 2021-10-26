<?php

declare(strict_types=1);

namespace App\Money;

final class YoulCoinFormatter
{
    public function format(string $amount): string
    {
        return YoulCoinCurrency::SYMBOL.$amount;
    }
}
