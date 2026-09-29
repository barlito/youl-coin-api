<?php

declare(strict_types=1);

namespace App\Service\Ledger;

// Invariant: sum of every wallet.amount must equal sum(Mint) - sum(Burn)
readonly class LedgerCheckResult
{
    public function __construct(
        public string $walletTotal,
        public string $mintTotal,
        public string $burnTotal,
    ) {
    }

    public function getExpectedWalletTotal(): string
    {
        $mintTotal = $this->mintTotal;
        $burnTotal = $this->burnTotal;

        return is_numeric($mintTotal) && is_numeric($burnTotal) ? bcsub($mintTotal, $burnTotal) : '0';
    }

    public function isBalanced(): bool
    {
        $walletTotal = $this->walletTotal;
        $expectedWalletTotal = $this->getExpectedWalletTotal();

        return is_numeric($walletTotal) && is_numeric($expectedWalletTotal) && 0 === bccomp($walletTotal, $expectedWalletTotal);
    }
}
