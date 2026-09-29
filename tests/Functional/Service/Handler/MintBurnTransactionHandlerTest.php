<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Repository\DiscordUserRepository;
use App\Repository\WalletRepository;
use App\Service\Handler\TransactionHandler;
use App\Service\Ledger\LedgerChecker;
use App\Validator\Entity\Transaction\TransactionConstraint;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

class MintBurnTransactionHandlerTest extends KernelTestCase
{
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    private EntityManagerInterface $entityManager;

    private TransactionHandler $transactionHandler;

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->transactionHandler = static::getContainer()->get(TransactionHandler::class);
    }

    public function testMintCreditsTheBankWithoutAWalletFrom(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletTo($this->bankWallet())
            ->setReason('Minting test coins')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('mint_test')
        ;

        $this->transactionHandler->handleTransaction($transaction);

        $this->assertSame('1000000001000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testBurnDebitsTheBankWithoutAWalletTo(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::BURN)
            ->setWalletFrom($this->bankWallet())
            ->setReason('Burning test coins')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('burn_test')
        ;

        $this->transactionHandler->handleTransaction($transaction);

        $this->assertSame('999999999000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testBurnBeyondTheBankBalanceIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('9999999999999999')
            ->setType(TransactionTypeEnum::BURN)
            ->setWalletFrom($this->bankWallet())
            ->setReason('Burning too much')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('burn_too_much')
        ;

        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The Burn should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(TransactionConstraint::NOT_ENOUGH_CURRENCY_IN_WALLET, $exception->getMessage());
        }

        $this->assertSame('1000000000000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testMintWithoutAReasonIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletTo($this->bankWallet())
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('mint_no_reason')
        ;

        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The Mint should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(TransactionConstraint::REASON_REQUIRED, $exception->getMessage());
        }

        $this->assertSame('1000000000000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testMintWithAWalletFromIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletFrom($this->userWallet())
            ->setWalletTo($this->bankWallet())
            ->setReason('Should be refused')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('mint_with_wallet_from')
        ;

        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The Mint should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(TransactionConstraint::MINT_WALLET_FROM_FORBIDDEN, $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidReasons(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces only' => ['     '];
        yield 'too short once trimmed' => ['  ab  '];
        yield 'over 500 characters' => [str_repeat('a', 501)];
    }

    #[DataProvider('invalidReasons')]
    public function testMintWithAnInvalidReasonIsRefused(string $reason): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletTo($this->bankWallet())
            ->setReason($reason)
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('mint_bad_reason')
        ;

        $this->assertRefused($transaction, TransactionConstraint::REASON_REQUIRED);
        $this->assertSame('1000000000000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testMintToAUserWalletIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletTo($this->userWallet())
            ->setReason('Should be refused')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('mint_to_user')
        ;

        $this->assertRefused($transaction, TransactionConstraint::MINT_WRONG_WALLET_TO);
    }

    public function testBurnFromAUserWalletIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::BURN)
            ->setWalletFrom($this->userWallet())
            ->setReason('Should be refused')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('burn_from_user')
        ;

        $this->assertRefused($transaction, TransactionConstraint::BURN_WRONG_WALLET_FROM);
    }

    public function testBurnWithAWalletToIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::BURN)
            ->setWalletFrom($this->bankWallet())
            ->setWalletTo($this->userWallet())
            ->setReason('Should be refused')
            ->setInitiatedBy($this->admin())
            ->setExternalIdentifier('burn_with_wallet_to')
        ;

        $this->assertRefused($transaction, TransactionConstraint::BURN_WALLET_TO_FORBIDDEN);
        $this->assertSame('1000000000000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testMintWithoutAnInitiatingAdminIsRefused(): void
    {
        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletTo($this->bankWallet())
            ->setReason('Nobody asked for it')
            ->setExternalIdentifier('mint_no_initiator')
        ;

        $this->assertRefused($transaction, TransactionConstraint::INITIATED_BY_REQUIRED);
        $this->assertSame('1000000000000', $this->fetchAmount(self::BANK_WALLET_ID));
    }

    public function testAMintThenABurnLeaveTheLedgerBalanced(): void
    {
        $genesis = $this->mintFixtureSupplyToTheBank();
        $ledgerChecker = static::getContainer()->get(LedgerChecker::class);
        $this->assertTrue($ledgerChecker->check()->isBalanced());

        $this->transactionHandler->handleTransaction(
            new Transaction()
                ->setAmount('5000')
                ->setType(TransactionTypeEnum::MINT)
                ->setWalletTo($this->bankWallet())
                ->setReason('Minting test coins')
                ->setInitiatedBy($this->admin())
                ->setExternalIdentifier('ledger_mint'),
        );
        $this->transactionHandler->handleTransaction(
            new Transaction()
                ->setAmount('2000')
                ->setType(TransactionTypeEnum::BURN)
                ->setWalletFrom($this->bankWallet())
                ->setReason('Burning test coins')
                ->setInitiatedBy($this->admin())
                ->setExternalIdentifier('ledger_burn'),
        );

        $result = $ledgerChecker->check();
        $this->assertTrue($result->isBalanced());
        $this->assertSame(bcadd($genesis, '3000'), $result->walletTotal);
        $this->assertSame('2000', $result->burnTotal);
    }

    private function assertRefused(Transaction $transaction, string $expectedMessage): void
    {
        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The transaction should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->getMessage());
        }
    }

    // Fixtures carry no genesis Mint: the ledger only balances once the existing supply is minted
    private function mintFixtureSupplyToTheBank(): string
    {
        $total = (string) $this->connection()->fetchOne('SELECT SUM(amount::numeric) FROM wallet');
        $this->connection()->executeStatement(
            <<<'SQL'
                INSERT INTO transaction (id, wallet_from_id, wallet_to_id, amount, external_identifier, type, reason, initiated_by_id, issuer_id, created_at, updated_at)
                VALUES (gen_random_uuid(), NULL, :bankWalletId, :amount, NULL, 'mint', 'Test genesis', NULL, NULL, now(), now())
                SQL,
            ['bankWalletId' => Ulid::fromString(self::BANK_WALLET_ID)->toRfc4122(), 'amount' => $total],
        );

        return $total;
    }

    private function admin(): DiscordUser
    {
        $admin = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => '188967649332428800']);
        $this->assertInstanceOf(DiscordUser::class, $admin);

        return $admin;
    }

    private function userWallet(): Wallet
    {
        $wallet = static::getContainer()->get(WalletRepository::class)->findOneBy(['discordUser' => '188967649332428800']);
        $this->assertInstanceOf(Wallet::class, $wallet);

        return $wallet;
    }

    private function bankWallet(): Wallet
    {
        $wallet = $this->entityManager->find(Wallet::class, self::BANK_WALLET_ID);
        $this->assertInstanceOf(Wallet::class, $wallet);

        return $wallet;
    }

    private function fetchAmount(string $walletId): string
    {
        return (string) $this->connection()->fetchOne(
            'SELECT amount FROM wallet WHERE id = :id',
            ['id' => Ulid::fromString($walletId)->toRfc4122()],
        );
    }

    private function connection(): Connection
    {
        return $this->entityManager->getConnection();
    }
}
