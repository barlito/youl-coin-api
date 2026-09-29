<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Service\Ledger\LedgerCheckResult;

readonly class EconomyDashboard
{
    /**
     * @param numeric-string            $totalSupply        minor units, every wallet
     * @param numeric-string            $bankBalance        minor units
     * @param numeric-string            $circulation        minor units, player wallets
     * @param numeric-string|null       $topTwoSharePercent null when nothing circulates
     * @param list<WalletBalance>       $topBalances
     * @param list<TransactionTypeFlow> $flowByType
     * @param list<DailyVolume>         $flowByDay          oldest first, empty days included
     */
    public function __construct(
        public LedgerCheckResult $ledger,
        public string $totalSupply,
        public string $bankBalance,
        public string $circulation,
        public int $playerWalletCount,
        public int $zeroBalancePlayers,
        public array $topBalances,
        public ?string $topTwoSharePercent,
        public array $flowByType,
        public array $flowByDay,
        public int $activePlayers,
        public int $periodDays,
    ) {
    }
}
