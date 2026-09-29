<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Service\Ledger\LedgerChecker;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

class EconomyStatsProvider
{
    private const int PERIOD_DAYS = 30;
    private const int TOP_BALANCES = 5;
    private const string BUSINESS_TIME_ZONE = 'Europe/Paris';

    public function __construct(
        private readonly Connection $connection,
        private readonly LedgerChecker $ledgerChecker,
        private readonly ClockInterface $clock,
    ) {
    }

    public function provide(): EconomyDashboard
    {
        $since = $this->getPeriodStart();
        $supply = $this->fetchSupply();
        $topBalances = $this->fetchTopBalances();

        return new EconomyDashboard(
            ledger: $this->ledgerChecker->check(),
            totalSupply: $supply['total'],
            bankBalance: $supply['bank'],
            circulation: $supply['players'],
            playerWalletCount: (int) $supply['player_count'],
            zeroBalancePlayers: (int) $supply['zero_count'],
            topBalances: $topBalances,
            topTwoSharePercent: $this->computeTopTwoShare($topBalances, $supply['players']),
            flowByType: $this->fetchFlowByType($since),
            flowByDay: $this->fetchFlowByDay($since),
            activePlayers: $this->fetchActivePlayers($since),
            periodDays: self::PERIOD_DAYS,
        );
    }

    // Midnight of the oldest displayed day, in Paris time
    private function getPeriodStart(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now())
            ->setTimezone(new \DateTimeZone(self::BUSINESS_TIME_ZONE))
            ->setTime(0, 0)
            ->modify(\sprintf('-%d days', self::PERIOD_DAYS - 1))
        ;
    }

    /**
     * @return array{total: numeric-string, bank: numeric-string, players: numeric-string, player_count: int|string, zero_count: int|string}
     */
    private function fetchSupply(): array
    {
        /** @var array{total: numeric-string, bank: numeric-string, players: numeric-string, player_count: int|string, zero_count: int|string} $row */
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT
                    COALESCE(SUM(amount::numeric), 0) AS total,
                    COALESCE(SUM(amount::numeric) FILTER (WHERE type = :bank), 0) AS bank,
                    COALESCE(SUM(amount::numeric) FILTER (WHERE type = :user), 0) AS players,
                    COUNT(*) FILTER (WHERE type = :user) AS player_count,
                    COUNT(*) FILTER (WHERE type = :user AND amount::numeric = 0) AS zero_count
                FROM wallet
                SQL,
            ['bank' => WalletTypeEnum::BANK->value, 'user' => WalletTypeEnum::USER->value],
        );

        return $row;
    }

    /**
     * @return list<WalletBalance>
     */
    private function fetchTopBalances(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT name, amount::numeric AS amount FROM wallet WHERE type = :user ORDER BY amount::numeric DESC, name LIMIT ' . self::TOP_BALANCES,
            ['user' => WalletTypeEnum::USER->value],
        );

        return array_map(fn (array $row): WalletBalance => new WalletBalance($row['name'], $this->toAmount($row['amount'])), $rows);
    }

    /**
     * @param list<WalletBalance> $topBalances
     *
     * @return numeric-string|null
     */
    private function computeTopTwoShare(array $topBalances, string $circulation): ?string
    {
        if (!is_numeric($circulation) || 0 === bccomp($circulation, '0')) {
            return null;
        }

        $topTwo = array_reduce(
            \array_slice($topBalances, 0, 2),
            static fn (string $carry, WalletBalance $balance): string => bcadd($carry, $balance->amount),
            '0',
        );

        return bcdiv(bcmul($topTwo, '100'), $circulation, 1);
    }

    /**
     * @return list<TransactionTypeFlow>
     */
    private function fetchFlowByType(\DateTimeImmutable $since): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT type, COUNT(*) AS count, SUM(amount::numeric) AS amount
                FROM transaction
                WHERE created_at >= :since
                GROUP BY type
                ORDER BY SUM(amount::numeric) DESC
                SQL,
            ['since' => $this->toUtcParameter($since)],
        );

        return array_map(
            fn (array $row): TransactionTypeFlow => new TransactionTypeFlow(TransactionTypeEnum::from($row['type']), (int) $row['count'], $this->toAmount($row['amount'])),
            $rows,
        );
    }

    /**
     * @return list<DailyVolume>
     */
    private function fetchFlowByDay(\DateTimeImmutable $since): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT (created_at AT TIME ZONE 'UTC' AT TIME ZONE :zone)::date AS day, COUNT(*) AS count, SUM(amount::numeric) AS amount
                FROM transaction
                WHERE created_at >= :since
                GROUP BY day
                SQL,
            ['zone' => self::BUSINESS_TIME_ZONE, 'since' => $this->toUtcParameter($since)],
        );

        /** @var array<string, array{count: int, amount: numeric-string}> $perDay */
        $perDay = [];
        foreach ($rows as $row) {
            $perDay[$row['day']] = ['count' => (int) $row['count'], 'amount' => $this->toAmount($row['amount'])];
        }

        $peak = '0';
        foreach ($perDay as $stats) {
            if (1 === bccomp($stats['amount'], $peak)) {
                $peak = $stats['amount'];
            }
        }

        $days = [];
        for ($offset = 0; $offset < self::PERIOD_DAYS; ++$offset) {
            $day = $since->modify(\sprintf('+%d days', $offset));
            $stats = $perDay[$day->format('Y-m-d')] ?? ['count' => 0, 'amount' => '0'];
            $percent = 0 === bccomp($peak, '0') ? 0 : (int) bcdiv(bcmul($stats['amount'], '100'), $peak);
            $days[] = new DailyVolume($day, $stats['count'], $stats['amount'], max(0, min(100, $percent)));
        }

        return $days;
    }

    private function fetchActivePlayers(\DateTimeImmutable $since): int
    {
        return (int) $this->connection->fetchOne(
            <<<'SQL'
                SELECT COUNT(*)
                FROM (
                    SELECT wallet_from_id AS wallet_id FROM transaction WHERE created_at >= :since
                    UNION
                    SELECT wallet_to_id FROM transaction WHERE created_at >= :since
                ) active
                JOIN wallet ON wallet.id = active.wallet_id AND wallet.type = :user
                SQL,
            ['since' => $this->toUtcParameter($since), 'user' => WalletTypeEnum::USER->value],
        );
    }

    /**
     * @return numeric-string
     */
    private function toAmount(mixed $value): string
    {
        $amount = (string) $value;
        if (!is_numeric($amount)) {
            throw new \UnexpectedValueException('Amount must be numeric.');
        }

        return $amount;
    }

    // created_at is stored in UTC
    private function toUtcParameter(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
