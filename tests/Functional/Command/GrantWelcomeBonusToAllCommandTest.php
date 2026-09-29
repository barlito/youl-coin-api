<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Enum\Roles\RoleEnum;
use App\Repository\DiscordUserRepository;
use App\Security\DiscordUserWhitelist;
use App\Service\Ledger\LedgerChecker;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GrantWelcomeBonusToAllCommandTest extends KernelTestCase
{
    private const string VELI = '189029821328785409';
    private const string STRANGER = '999999999999999999';

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        self::bootKernel();
        $this->resetEconomyLikeTheMigration();
        $this->removeWalletOf(self::VELI);
        $this->addNonWhitelistedPlayer();
    }

    public function testEveryWhitelistedPlayerGetsAWalletAndTheBonus(): void
    {
        $bonus = $this->bonusAmount();
        $this->mintToTheBank(bcmul($bonus, (string) $this->whitelistedPlayerCount()));

        $tester = $this->commandTester();

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertSame($this->whitelistedPlayerCount(), $this->countWalletsHolding($bonus));
        $this->assertNotFalse($this->walletAmountOf(self::VELI));
        $this->assertFalse($this->walletAmountOf(self::STRANGER));
        $this->assertSame('0', $this->bankAmount());
        $this->assertTrue(static::getContainer()->get(LedgerChecker::class)->check()->isBalanced());
    }

    public function testASecondRunGrantsNothing(): void
    {
        $this->mintToTheBank(bcmul($this->bonusAmount(), (string) ($this->whitelistedPlayerCount() + 1)));
        $this->commandTester()->execute([]);
        $bankAfterFirstRun = $this->bankAmount();

        $tester = $this->commandTester();

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('déjà reçu', $tester->getDisplay());
        $this->assertSame($bankAfterFirstRun, $this->bankAmount());
        $this->assertSame($this->whitelistedPlayerCount(), $this->countWelcomeBonuses());
    }

    public function testADryRunWritesNothing(): void
    {
        $this->mintToTheBank(bcmul($this->bonusAmount(), (string) $this->whitelistedPlayerCount()));

        $tester = $this->commandTester();

        $this->assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        $this->assertStringContainsString('à créer', $tester->getDisplay());
        $this->assertSame(0, $this->countWelcomeBonuses());
        $this->assertFalse($this->walletAmountOf(self::VELI));
    }

    public function testAnUnderfundedBankWritesNothing(): void
    {
        $this->mintToTheBank(bcsub(bcmul($this->bonusAmount(), (string) $this->whitelistedPlayerCount()), '1'));

        $tester = $this->commandTester();

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('MINT', $tester->getDisplay());
        $this->assertSame(0, $this->countWelcomeBonuses());
        $this->assertFalse($this->walletAmountOf(self::VELI));
    }

    public function testADisabledBonusStopsTheCommand(): void
    {
        $this->connection()->executeStatement("UPDATE economy_settings SET welcome_bonus_amount = '0'");
        $this->mintToTheBank('1000000000000');

        $this->assertSame(Command::FAILURE, $this->commandTester()->execute([]));
        $this->assertSame(0, $this->countWelcomeBonuses());
    }

    private function resetEconomyLikeTheMigration(): void
    {
        $this->connection()->executeStatement('DELETE FROM transaction');
        $this->connection()->executeStatement("UPDATE wallet SET amount = '0', updated_at = NOW()");
    }

    private function removeWalletOf(string $discordId): void
    {
        $this->connection()->executeStatement('DELETE FROM wallet WHERE discord_user_id = :id', ['id' => $discordId]);
    }

    private function addNonWhitelistedPlayer(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(new DiscordUser()->setDiscordId(self::STRANGER)->setUsername('Stranger')->setRoles([RoleEnum::ROLE_USER->value]));
        $entityManager->flush();
        $entityManager->clear();
    }

    // Keeps the ledger balanced: the bank is only ever funded by a Mint
    private function mintToTheBank(string $amount): void
    {
        $this->connection()->executeStatement("UPDATE wallet SET amount = :amount WHERE type = 'bank'", ['amount' => $amount]);
        $this->connection()->executeStatement(
            <<<'SQL'
                INSERT INTO transaction (id, amount, wallet_from_id, wallet_to_id, type, reason, created_at, updated_at)
                SELECT gen_random_uuid(), :amount, NULL, id, 'mint', 'Test supply', NOW(), NOW() FROM wallet WHERE type = 'bank'
                SQL,
            ['amount' => $amount],
        );
    }

    private function whitelistedPlayerCount(): int
    {
        $whitelist = static::getContainer()->get(DiscordUserWhitelist::class);
        $players = static::getContainer()->get(DiscordUserRepository::class)->findAll();

        return \count(array_filter($players, static fn (DiscordUser $player): bool => $whitelist->isAllowed($player->getDiscordId())));
    }

    private function bonusAmount(): string
    {
        return EconomySettings::DEFAULT_WELCOME_BONUS_AMOUNT;
    }

    private function bankAmount(): string
    {
        return (string) $this->connection()->fetchOne("SELECT amount FROM wallet WHERE type = 'bank'");
    }

    private function walletAmountOf(string $discordId): string | false
    {
        return $this->connection()->fetchOne('SELECT amount FROM wallet WHERE discord_user_id = :id', ['id' => $discordId]);
    }

    private function countWalletsHolding(string $amount): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM wallet WHERE type = 'user' AND amount = :amount", ['amount' => $amount]);
    }

    private function countWelcomeBonuses(): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM transaction WHERE type = 'welcome_bonus'");
    }

    private function commandTester(): CommandTester
    {
        return new CommandTester(new Application(self::$kernel)->find('app:welcome-bonus:grant-all'));
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
