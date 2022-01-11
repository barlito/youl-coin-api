<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AmountValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value) || !$this->isPositive($value)) {
            $this->context->buildViolation($constraint::AMOUNT_NOT_POSITIVE_INTEGER_MESSAGE)
                ->addViolation()
            ;
        }
    }

    private function isPositive(string $value): bool
    {
        return bccomp($value, '0') > 0;
    }
}
