<?php

declare(strict_types=1);

namespace App\Validator\DTO;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BankWallet extends Constraint
{
    public const NO_BANK_WALLET_FOUND = 'You must select the Bank Wallet as transaction receiver or sender.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
