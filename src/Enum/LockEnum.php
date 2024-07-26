<?php

declare(strict_types=1);

namespace App\Enum;

enum LockEnum: string
{
    case TRANSACTION_LOCK = 'transaction_lock';
}
