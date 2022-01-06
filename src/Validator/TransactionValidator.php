<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Wallet;
use App\Message\TransactionMessage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TransactionValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Transaction) {
            throw new UnexpectedTypeException($constraint, Transaction::class);
        }

        if (!$value instanceof TransactionMessage) {
            throw new UnexpectedTypeException($constraint, TransactionMessage::class);
        }

        if (
            !$value->getWalletFrom() instanceof Wallet
            || !\is_string($value->getAmount())
            || !is_numeric($value->getAmount())
        ) {
            return;
        }

        if (!$this->isPositive($value)) {
            $this->context->buildViolation($constraint::NOT_ENOUGH_IN_WALLET)
                ->addViolation()
            ;
        }
    }

    private function isPositive(TransactionMessage $value): bool
    {
        return bcsub($value->getWalletFrom()->getAmount(), $value->getAmount()) > 0;
    }
}
