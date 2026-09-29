<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transaction;

final readonly class WalletHistoryPage
{
    /**
     * @param Transaction[] $transactions
     */
    public function __construct(public array $transactions, public int $totalItems)
    {
    }
}
