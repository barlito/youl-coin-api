<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Amount extends Constraint
{
    public const AMOUNT_NOT_POSITIVE_INTEGER_MESSAGE = 'The amount value is not a positive integer';
}
