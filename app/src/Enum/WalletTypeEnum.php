<?php

declare(strict_types=1);

namespace App\Enum;

enum WalletTypeEnum: string
{
    case USER = 'user';
    case BANK = 'bank';
}
