<?php

declare(strict_types=1);

namespace App\Enum;

enum BankWalletTransactionTypeEnum: string
{
    case GIVE = 'give';

    case TAKE = 'take';
}
