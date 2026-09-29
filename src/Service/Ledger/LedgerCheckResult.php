<?php

declare(strict_types=1);

namespace App\Service\Ledger;

// Invariant: sum of every wallet.amount must equal sum(Mint) - sum(Burn)
readonly class LedgerCheckResult
{
    /** @var numeric-string */
    public string $walletTotal;

    /** @var numeric-string */
    public string $mintTotal;

    /** @var numeric-string */
    public string $burnTotal;

    public function __construct(string $walletTotal, string $mintTotal, string $burnTotal)
    {
        if (!is_numeric($walletTotal) || !is_numeric($mintTotal) || !is_numeric($burnTotal)) {
            throw new \UnexpectedValueException('Ledger totals must be numeric.');
        }

        $this->walletTotal = $walletTotal;
        $this->mintTotal = $mintTotal;
        $this->burnTotal = $burnTotal;
    }

    /**
     * @return numeric-string
     */
    public function getExpectedWalletTotal(): string
    {
        return bcsub($this->mintTotal, $this->burnTotal);
    }

    /**
     * @return numeric-string
     */
    public function getDifference(): string
    {
        return ltrim(bcsub($this->walletTotal, $this->getExpectedWalletTotal()), '-');
    }

    public function isBalanced(): bool
    {
        return 0 === bccomp($this->walletTotal, $this->getExpectedWalletTotal());
    }
}
