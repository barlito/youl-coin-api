<?php

declare(strict_types=1);

namespace App\Service\Admin;

readonly class WalletBalance
{
    /**
     * @param numeric-string $amount minor units
     */
    public function __construct(public string $name, public string $amount)
    {
    }
}
