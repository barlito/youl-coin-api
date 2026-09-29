<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Admin;

use App\Enum\TransactionTypeEnum;
use App\Service\Admin\EconomyStatsProvider;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

class EconomyStatsProviderTest extends KernelTestCase
{
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    private Connection $connection;

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        self::bootKernel();
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testSupplyAndConcentrationOnTheFixtures(): void
    {
        $dashboard = $this->provider()->provide();

        $playerTotal = (string) $this->connection->fetchOne("SELECT SUM(amount::numeric) FROM wallet WHERE type = 'user'");

        $this->assertSame('1000000000000', $dashboard->bankBalance);
        $this->assertSame($playerTotal, $dashboard->circulation);
        $this->assertSame(bcadd($playerTotal, '1000000000000'), $dashboard->totalSupply);
        $this->assertSame(7, $dashboard->playerWalletCount);
        $this->assertSame(0, $dashboard->zeroBalancePlayers);

        $this->assertSame(['Wallet Barlito', 'Wallet Juju', 'Wallet Farph', 'Wallet Weeby', 'Wallet Benj'], array_column($this->toArray($dashboard->topBalances), 'name'));
        $this->assertSame('900000000000', $dashboard->topBalances[0]->amount);
        $this->assertSame(bcdiv(bcmul('1700000000000', '100'), $playerTotal, 1), $dashboard->topTwoSharePercent);
    }

    public function testFlowsAndActivePlayersCoverTheLastThirtyDays(): void
    {
        $this->connection->executeStatement("UPDATE transaction SET created_at = now() - interval '40 days' WHERE external_identifier IS DISTINCT FROM 'fixture'");
        $this->insertTransaction(TransactionTypeEnum::MINT, '500', null, self::BANK_WALLET_ID, 'now()');

        $dashboard = $this->provider()->provide();

        $byType = [];
        foreach ($dashboard->flowByType as $flow) {
            $byType[$flow->type->value] = [$flow->count, $flow->amount];
        }
        $this->assertSame(['classic' => [1, '1000000000'], 'mint' => [1, '500']], $byType);

        $this->assertCount(30, $dashboard->flowByDay);
        $today = $dashboard->flowByDay[29];
        $this->assertSame(2, $today->count);
        $this->assertSame(30, $dashboard->periodDays);
        $this->assertSame(2, array_sum(array_map(static fn ($day): int => $day->count, $dashboard->flowByDay)));
        $this->assertContains(100, array_map(static fn ($day): int => $day->percent, $dashboard->flowByDay));

        // Barlito and Juju traded; the mint only touches the bank wallet
        $this->assertSame(2, $dashboard->activePlayers);
    }

    public function testLedgerInvariantHoldsThenBreaksAfterADirectUpdate(): void
    {
        $total = (string) $this->connection->fetchOne('SELECT SUM(amount::numeric) FROM wallet');
        $this->insertTransaction(TransactionTypeEnum::MINT, $total, null, self::BANK_WALLET_ID, 'now()');

        $this->assertTrue($this->provider()->provide()->ledger->isBalanced());

        $this->connection->executeStatement(
            'UPDATE wallet SET amount = amount::numeric + 7 WHERE id = :id',
            ['id' => Ulid::fromString(self::BANK_WALLET_ID)->toRfc4122()],
        );

        $ledger = $this->provider()->provide()->ledger;
        $this->assertFalse($ledger->isBalanced());
        $this->assertSame('7', $ledger->getDifference());
    }

    private function provider(): EconomyStatsProvider
    {
        return static::getContainer()->get(EconomyStatsProvider::class);
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    private function toArray(array $objects): array
    {
        return array_map(static fn (object $object): array => get_object_vars($object), $objects);
    }

    private function insertTransaction(TransactionTypeEnum $type, string $amount, ?string $fromId, ?string $toId, string $createdAt): void
    {
        $this->connection->executeStatement(
            \sprintf(
                <<<'SQL'
                    INSERT INTO transaction (id, wallet_from_id, wallet_to_id, amount, type, created_at, updated_at)
                    VALUES (gen_random_uuid(), :from, :to, :amount, :type, %s, %s)
                    SQL,
                $createdAt,
                $createdAt,
            ),
            [
                'from' => null === $fromId ? null : Ulid::fromString($fromId)->toRfc4122(),
                'to' => null === $toId ? null : Ulid::fromString($toId)->toRfc4122(),
                'amount' => $amount,
                'type' => $type->value,
            ],
        );
    }
}
