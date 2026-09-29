<?php

declare(strict_types=1);

namespace App\Enum;

// Direction of a Transaction from the viewer wallet's point of view
enum TransactionDirectionEnum: string
{
    case IN = 'in';
    case OUT = 'out';
}
