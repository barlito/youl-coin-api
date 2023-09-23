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

    public const SEASON_REWARD_WRONG_WALLET_FROM = 'Season Reward Transaction must have the Bank Wallet as Wallet From.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
