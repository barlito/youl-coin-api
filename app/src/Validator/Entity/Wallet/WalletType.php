<?php

declare(strict_types=1);

namespace App\Validator\Entity\Wallet;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class WalletType extends Constraint
{
    public const UNIQUE_BANK_WALLET_ERROR = 'Only one bank wallet can exist at a time.';
}
