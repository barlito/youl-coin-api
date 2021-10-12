<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class Transaction extends Constraint
{
    public const NOT_ENOUGH_IN_WALLET = 'Not enough coins in from wallet';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
