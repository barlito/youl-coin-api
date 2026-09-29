<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiResource;

use App\ApiResource\WalletTransactionView;
use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionDirectionEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use PHPUnit\Framework\TestCase;

class WalletTransactionViewTest extends TestCase
{
    public function testOutgoingTransactionBetweenTwoPlayers(): void
    {
        $viewer = $this->makeWallet(WalletTypeEnum::USER, '111');
        $counterpart = $this->makeWallet(WalletTypeEnum::USER, '222');

        $transaction = $this->makeTransaction(TransactionTypeEnum::CLASSIC, $viewer, $counterpart);

        $view = WalletTransactionView::fromTransaction($transaction, $viewer);

        $this->assertSame(TransactionDirectionEnum::OUT, $view->direction);
        $this->assertSame(WalletTypeEnum::USER, $view->counterpartType);
        $this->assertSame('222', $view->counterpartDiscordId);
    }

    public function testIncomingTransactionBetweenTwoPlayers(): void
    {
        $viewer = $this->makeWallet(WalletTypeEnum::USER, '111');
        $counterpart = $this->makeWallet(WalletTypeEnum::USER, '222');

        $transaction = $this->makeTransaction(TransactionTypeEnum::CLASSIC, $counterpart, $viewer);

        $view = WalletTransactionView::fromTransaction($transaction, $viewer);

        $this->assertSame(TransactionDirectionEnum::IN, $view->direction);
        $this->assertSame(WalletTypeEnum::USER, $view->counterpartType);
        $this->assertSame('222', $view->counterpartDiscordId);
    }

    // WelcomeBonus is bank-funded: always an "in" entry, with no discordId leaked for the bank
    public function testWelcomeBonusIsInFromTheBank(): void
    {
        $bank = $this->makeWallet(WalletTypeEnum::BANK, null);
        $viewer = $this->makeWallet(WalletTypeEnum::USER, '111');

        $transaction = $this->makeTransaction(TransactionTypeEnum::WELCOME_BONUS, $bank, $viewer);

        $view = WalletTransactionView::fromTransaction($transaction, $viewer);

        $this->assertSame(TransactionDirectionEnum::IN, $view->direction);
        $this->assertSame(WalletTypeEnum::BANK, $view->counterpartType);
        $this->assertNull($view->counterpartDiscordId);
    }

    // Defensive: Mint/Burn never reach a player's history in practice (neither wallet is ever a player wallet)
    public function testMintToleratesANullWalletFrom(): void
    {
        $bank = $this->makeWallet(WalletTypeEnum::BANK, null);

        $transaction = $this->makeTransaction(TransactionTypeEnum::MINT, null, $bank);

        $view = WalletTransactionView::fromTransaction($transaction, $bank);

        $this->assertSame(TransactionDirectionEnum::IN, $view->direction);
        $this->assertNull($view->counterpartType);
        $this->assertNull($view->counterpartDiscordId);
    }

    public function testBurnToleratesANullWalletTo(): void
    {
        $bank = $this->makeWallet(WalletTypeEnum::BANK, null);

        $transaction = $this->makeTransaction(TransactionTypeEnum::BURN, $bank, null);

        $view = WalletTransactionView::fromTransaction($transaction, $bank);

        $this->assertSame(TransactionDirectionEnum::OUT, $view->direction);
        $this->assertNull($view->counterpartType);
        $this->assertNull($view->counterpartDiscordId);
    }

    public function testNeverExposesExternalIdentifierOrIssuer(): void
    {
        $viewer = $this->makeWallet(WalletTypeEnum::USER, '111');
        $counterpart = $this->makeWallet(WalletTypeEnum::USER, '222');

        $transaction = $this->makeTransaction(TransactionTypeEnum::CLASSIC, $viewer, $counterpart)
            ->setExternalIdentifier('should-never-leak')
        ;

        $view = WalletTransactionView::fromTransaction($transaction, $viewer);

        $this->assertFalse(property_exists($view, 'externalIdentifier'));
        $this->assertFalse(property_exists($view, 'issuer'));
    }

    private function makeWallet(WalletTypeEnum $type, ?string $discordId): Wallet
    {
        $wallet = new Wallet()->setType($type);

        if (null !== $discordId) {
            $wallet->setDiscordUser(new DiscordUser()->setDiscordId($discordId));
        }

        return $wallet;
    }

    private function makeTransaction(TransactionTypeEnum $type, ?Wallet $from, ?Wallet $to): Transaction
    {
        return new Transaction()
            ->setId('a1b2c3d4-0000-4000-8000-000000000099')
            ->setType($type)
            ->setAmount('1000')
            ->setWalletFrom($from)
            ->setWalletTo($to)
            ->setCreatedAt(new \DateTime('-1 hour'))
        ;
    }
}
