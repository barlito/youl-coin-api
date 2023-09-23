<?php

declare(strict_types=1);

namespace App\Validator\Entity\Wallet;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DiscordUserWalletExist extends Constraint
{
    public const WALLET_NOT_FOUND = 'The Wallet with the given Discord ID was not found.';
}
