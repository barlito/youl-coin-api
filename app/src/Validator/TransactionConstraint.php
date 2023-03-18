<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TransactionConstraint extends Constraint
{
    public const NOT_ENOUGH_CURRENCY_IN_WALLET = 'Not enough coins in from wallet.';

    public const SAME_WALLET_FOR_TRANSACTION = 'WalletFrom and WalletTo are the same.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
