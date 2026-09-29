<?php

declare(strict_types=1);

namespace App\Service\Ledger;

use App\Enum\TransactionTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

class LedgerChecker
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function check(): LedgerCheckResult
    {
        // One statement = one snapshot: the three totals cannot be skewed by a concurrent transaction
        $totals = $this->entityManager->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    (SELECT COALESCE(SUM(amount::numeric), 0) FROM wallet) AS wallet_total,
                    (SELECT COALESCE(SUM(amount::numeric), 0) FROM transaction WHERE type = :mint) AS mint_total,
                    (SELECT COALESCE(SUM(amount::numeric), 0) FROM transaction WHERE type = :burn) AS burn_total
                SQL,
            ['mint' => TransactionTypeEnum::MINT->value, 'burn' => TransactionTypeEnum::BURN->value],
        );

        if (false === $totals) {
            throw new \LogicException('The ledger totals query returned no row.');
        }

        return new LedgerCheckResult(
            walletTotal: (string) $totals['wallet_total'],
            mintTotal: (string) $totals['mint_total'],
            burnTotal: (string) $totals['burn_total'],
        );
    }
}
