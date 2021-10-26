<?php

declare(strict_types=1);

namespace App\Enum;

class TransactionTypeEnum
{
    public const CLASSIC = 'classic';
    public const PREDICTION_DEPOSIT = 'prediction_deposit';
    public const PREDICTION_GAIN = 'prediction_gain';
    
    public const VALUES = [
        1 => self::CLASSIC,
        2 => self::PREDICTION_DEPOSIT,
        3 => self::PREDICTION_GAIN,
    ];
}
