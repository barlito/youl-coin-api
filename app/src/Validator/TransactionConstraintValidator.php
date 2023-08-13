<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Transaction;
use App\Entity\Wallet;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TransactionConstraintValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TransactionConstraint) {
            throw new UnexpectedTypeException($constraint, TransactionConstraint::class);
        }

        if (!$value instanceof Transaction) {
            throw new UnexpectedTypeException($constraint, Transaction::class);
        }

        if (
            !$value->getWalletFrom() instanceof Wallet
            || !\is_string($value->getAmount())
            || !is_numeric($value->getAmount())
        ) {
            return;
        }

        if ($value->getWalletFrom() === $value->getWalletTo()) {
            $this->context->buildViolation($constraint::SAME_WALLET_FOR_TRANSACTION)
                ->addViolation()
            ;
        }

        if (!$this->isPositive($value)) {
            $this->context->buildViolation($constraint::NOT_ENOUGH_CURRENCY_IN_WALLET)
                ->addViolation()
            ;
        }
    }

    private function isPositive(Transaction $value): bool
    {
        return bcsub($value->getWalletFrom()->getAmount(), $value->getAmount()) > 0;
    }
}
