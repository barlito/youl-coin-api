<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Ulid;

class LedgerCheckCommandTest extends KernelTestCase
{
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        self::bootKernel();
    }

    public function testTheLedgerIsBalancedOnceGenesisCoversTheFixtureWallets(): void
    {
        $this->mintFixtureSupplyToTheBank();

        $tester = $this->commandTester();
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Ledger balanced', $tester->getDisplay());
    }

    public function testAMismatchIsDetectedAfterADirectSqlUpdate(): void
    {
        $this->mintFixtureSupplyToTheBank();

        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement(
            'UPDATE wallet SET amount = amount::numeric + 1 WHERE id = :id',
            ['id' => Ulid::fromString(self::BANK_WALLET_ID)->toRfc4122()],
        );

        $tester = $this->commandTester();
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Ledger mismatch', $tester->getDisplay());
    }

    // Fixtures do not carry a genesis Mint (it would block deleting the bank wallet in BankWalletCreate.feature): mint it here instead
    private function mintFixtureSupplyToTheBank(): void
    {
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $total = (string) $connection->fetchOne('SELECT SUM(amount::numeric) FROM wallet');

        $connection->executeStatement(
            <<<'SQL'
                INSERT INTO transaction (id, wallet_from_id, wallet_to_id, amount, external_identifier, type, reason, initiated_by_id, issuer_id, created_at, updated_at)
                VALUES (gen_random_uuid(), NULL, :bankWalletId, :amount, NULL, 'mint', 'Test genesis', NULL, NULL, now(), now())
                SQL,
            ['bankWalletId' => Ulid::fromString(self::BANK_WALLET_ID)->toRfc4122(), 'amount' => $total],
        );
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('app:ledger:check'));
    }
}
