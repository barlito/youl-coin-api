<?php

declare(strict_types=1);

namespace App\Validator\Entity\Transaction;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AmountValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Amount) {
            throw new UnexpectedTypeException($constraint, Amount::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Minor units only: canonical digits, no sign, decimals, exponent, spaces or leading zeros
        if ((!\is_int($value) && !\is_string($value)) || 1 !== preg_match('/^[1-9]\d*$/', (string) $value)) {
            $this->context->buildViolation($constraint::AMOUNT_NOT_POSITIVE_INTEGER_MESSAGE)
                ->addViolation()
            ;
        }
    }
}
