<?php

declare(strict_types=1);

namespace App\Service\Ledger;

use Doctrine\ORM\EntityManagerInterface;

// Reusable by both app:ledger:check and the future economy dashboard
class LedgerChecker
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function check(): LedgerCheckResult
    {
        $connection = $this->entityManager->getConnection();

        return new LedgerCheckResult(
            walletTotal: (string) $connection->fetchOne('SELECT COALESCE(SUM(amount::numeric), 0) FROM wallet'),
            mintTotal: (string) $connection->fetchOne("SELECT COALESCE(SUM(amount::numeric), 0) FROM transaction WHERE type = 'mint'"),
            burnTotal: (string) $connection->fetchOne("SELECT COALESCE(SUM(amount::numeric), 0) FROM transaction WHERE type = 'burn'"),
        );
    }
}
