<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Enum\TransactionTypeEnum;

readonly class TransactionTypeFlow
{
    /**
     * @param numeric-string $amount minor units
     */
    public function __construct(public TransactionTypeEnum $type, public int $count, public string $amount)
    {
    }
}
