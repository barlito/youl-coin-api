<?php

declare(strict_types=1);

namespace App\Validator\Entity\Transaction;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TransactionConstraint extends Constraint
{
    public const NOT_ENOUGH_CURRENCY_IN_WALLET = 'Not enough coins in from wallet.';

    public const SAME_WALLET_FOR_TRANSACTION = 'WalletFrom and WalletTo are the same.';

    public const AIR_DROP_WRONG_WALLET_FROM = 'AirDrop Transaction must have the Bank Wallet as Wallet From.';

    public const REGULATION_NO_BANK_WALLET = 'Regulation Transaction must have the Bank Wallet as Wallet From or Wallet To.';

    public const WALLET_REQUIRED = 'This value should not be blank.';

    public const MINT_WALLET_FROM_FORBIDDEN = 'Mint Transaction must not have a Wallet From.';

    public const MINT_WRONG_WALLET_TO = 'Mint Transaction must have the Bank Wallet as Wallet To.';

    public const BURN_WALLET_TO_FORBIDDEN = 'Burn Transaction must not have a Wallet To.';

    public const BURN_WRONG_WALLET_FROM = 'Burn Transaction must have the Bank Wallet as Wallet From.';

    public const REASON_REQUIRED = 'Mint and Burn Transactions must have a reason between 3 and 500 characters.';

    public const INITIATED_BY_REQUIRED = 'Mint and Burn Transactions must have an initiating admin.';
    public const WELCOME_BONUS_WRONG_WALLETS = 'Welcome Bonus Transaction must go from the Bank Wallet to a user Wallet.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
