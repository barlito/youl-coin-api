<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Service\Handler\TransactionHandler;
use App\Validator\Entity\Transaction\TransactionConstraint;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

class TransactionHandlerTest extends KernelTestCase
{
    private const string WALLET_FARPH = '01FPD1DRHVBMZEM5EGS95F5N3E';
    private const string WALLET_JUJU = '01FPD1DNKVFS5GGBPVXBT3YQ01';

    private EntityManagerInterface $entityManager;
    private TransactionHandler $transactionHandler;

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->transactionHandler = static::getContainer()->get(TransactionHandler::class);
    }

    public function testWholeBalanceCanBeSpent(): void
    {
        $this->transactionHandler->handleTransaction($this->buildTransaction('700000000000'));

        $this->assertSame('0', $this->fetchAmount(self::WALLET_FARPH));
        $this->assertSame('1500000000000', $this->fetchAmount(self::WALLET_JUJU));
    }

    public function testBalanceIsCheckedAgainstTheDatabaseNotTheLoadedEntity(): void
    {
        $transaction = $this->buildTransaction('1000');
        // Another process spent the coins after this request loaded the wallet.
        $this->connection()->executeStatement(
            'UPDATE wallet SET amount = :amount WHERE id = :id',
            ['amount' => '999', 'id' => Ulid::fromString(self::WALLET_FARPH)->toRfc4122()],
        );

        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The transaction should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(TransactionConstraint::NOT_ENOUGH_CURRENCY_IN_WALLET, $exception->getMessage());
        }

        $this->assertSame('999', $this->fetchAmount(self::WALLET_FARPH));
        $this->assertSame('800000000000', $this->fetchAmount(self::WALLET_JUJU));
    }

    public function testTransactionWaitsForTheWalletRowLock(): void
    {
        $transaction = $this->buildTransaction('1000');

        $otherConnection = DriverManager::getConnection($this->connection()->getParams());
        $otherConnection->beginTransaction();
        $otherConnection->executeQuery(
            'SELECT id FROM wallet WHERE id = :id FOR UPDATE',
            ['id' => Ulid::fromString(self::WALLET_FARPH)->toRfc4122()],
        );

        $this->connection()->executeStatement("SET lock_timeout = '300ms'");

        try {
            $this->expectException(DriverException::class);
            $this->expectExceptionMessageMatches('/SQLSTATE\[55P03\]/');
            $this->transactionHandler->handleTransaction($transaction);
        } finally {
            $otherConnection->rollBack();
            $otherConnection->close();
        }
    }

    private function buildTransaction(string $amount): Transaction
    {
        return new Transaction()
            ->setAmount($amount)
            ->setWalletFrom($this->entityManager->find(Wallet::class, self::WALLET_FARPH))
            ->setWalletTo($this->entityManager->find(Wallet::class, self::WALLET_JUJU))
            ->setType(TransactionTypeEnum::CLASSIC)
            ->setExternalIdentifier('handler_test')
        ;
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
