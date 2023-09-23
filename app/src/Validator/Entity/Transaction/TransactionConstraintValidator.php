<?php

declare(strict_types=1);

namespace App\Validator\Entity\Transaction;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
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
            || !$value->getWalletTo() instanceof Wallet
            || !\is_string($value->getAmount())
            || !is_numeric($value->getAmount())
        ) {
            return;
        }

        $this->validateSameWallet($value, $constraint);

        $this->positiveAmount($value, $constraint);

        $this->validateAirDropType($value, $constraint);
        $this->validateRegulationType($value, $constraint);
        $this->validateSeasonRewardType($value, $constraint);
    }

    private function validateSameWallet(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if ($transaction->getWalletFrom() === $transaction->getWalletTo()) {
            $this->context->buildViolation($constraint::SAME_WALLET_FOR_TRANSACTION)
                ->addViolation()
            ;
        }
    }

    private function positiveAmount(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if (!$this->isPositive($transaction)) {
            $this->context->buildViolation($constraint::NOT_ENOUGH_CURRENCY_IN_WALLET)
                ->addViolation()
            ;
        }
    }

    private function isPositive(Transaction $value): bool
    {
        return bcsub($value->getWalletFrom()->getAmount(), $value->getAmount()) > 0;
    }

    private function validateAirDropType(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if (
            TransactionTypeEnum::AIR_DROP === $transaction->getType()
            && WalletTypeEnum::BANK !== $transaction->getWalletFrom()->getType()
        ) {
            $this->context->buildViolation($constraint::AIR_DROP_WRONG_WALLET_FROM)
                ->addViolation()
            ;
        }
    }

    private function validateRegulationType(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if (
            TransactionTypeEnum::REGULATION === $transaction->getType()
            && (WalletTypeEnum::BANK !== $transaction->getWalletFrom()->getType()
                && WalletTypeEnum::BANK !== $transaction->getWalletTo()->getType())
        ) {
            $this->context->buildViolation($constraint::REGULATION_NO_BANK_WALLET)
                ->addViolation()
            ;
        }
    }

    private function validateSeasonRewardType(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if (
            TransactionTypeEnum::SEASON_REWARD === $transaction->getType()
            && WalletTypeEnum::BANK !== $transaction->getWalletFrom()->getType()
        ) {
            $this->context->buildViolation($constraint::SEASON_REWARD_WRONG_WALLET_FROM)
                ->addViolation();
        }
    }
}
