<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Repository\WalletRepository;
use App\Service\Handler\TransactionHandler;
use App\Validator\Entity\Transaction\TransactionConstraint;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
        $walletRepository = static::getContainer()->get(WalletRepository::class);
        $userWallet = $walletRepository->findOneBy(['discordUser' => '188967649332428800']);
        $this->assertInstanceOf(Wallet::class, $userWallet);

        $transaction = new Transaction()
            ->setAmount('1000')
            ->setType(TransactionTypeEnum::MINT)
            ->setWalletFrom($userWallet)
            ->setWalletTo($this->bankWallet())
            ->setReason('Should be refused')
            ->setExternalIdentifier('mint_with_wallet_from')
        ;

        try {
            $this->transactionHandler->handleTransaction($transaction);
            $this->fail('The Mint should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(TransactionConstraint::MINT_WALLET_FROM_FORBIDDEN, $exception->getMessage());
        }
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
