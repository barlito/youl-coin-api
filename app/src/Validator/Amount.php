<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Amount extends Constraint
{
    public const AMOUNT_NOT_POSITIVE_INTEGER_MESSAGE = 'The amount value is not a positive integer';
}
