<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AmountValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Amount) {
            throw new UnexpectedTypeException($constraint, Amount::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!is_numeric($value) || !$this->isPositive($value)) {
            $this->context->buildViolation($constraint::AMOUNT_NOT_POSITIVE_INTEGER_MESSAGE)
                ->addViolation();
        }
    }

    private function isPositive(string $value): bool
    {
        return bccomp($value, '0') > 0;
    }
}
