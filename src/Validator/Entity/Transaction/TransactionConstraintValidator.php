<?php

declare(strict_types=1);

namespace App\Validator\Entity\Transaction;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
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

        if (!\is_string($value->getAmount()) || !is_numeric($value->getAmount())) {
            return;
        }

        if ($value->getType()?->isSupplyChange()) {
            $this->validateMintOrBurn($value, $constraint);

            return;
        }

        $walletFrom = $value->getWalletFrom();
        $walletTo = $value->getWalletTo();
        if (!$walletFrom instanceof Wallet) {
            $this->context->buildViolation($constraint::WALLET_REQUIRED)->atPath('walletFrom')->setCode(NotBlank::IS_BLANK_ERROR)->addViolation();
        }
        if (!$walletTo instanceof Wallet) {
            $this->context->buildViolation($constraint::WALLET_REQUIRED)->atPath('walletTo')->setCode(NotBlank::IS_BLANK_ERROR)->addViolation();
        }
        if (!$walletFrom instanceof Wallet || !$walletTo instanceof Wallet) {
            return;
        }

        $this->validateSameWallet($value, $constraint);

        $this->validateEnoughCoins($value, $constraint);

        $this->validateAirDropType($value, $constraint, $walletFrom);
        $this->validateRegulationType($value, $constraint, $walletFrom, $walletTo);
        $this->validateWelcomeBonusType($value, $constraint, $walletFrom, $walletTo);
        $this->validateAppTypes($value, $constraint, $walletFrom, $walletTo);
    }

    // Mint credits the bank out of nowhere (no balance check), Burn debits it
    private function validateMintOrBurn(Transaction $transaction, TransactionConstraint $constraint): void
    {
        $this->validateReason($transaction, $constraint);

        if (!$transaction->getInitiatedBy() instanceof DiscordUser) {
            $this->context->buildViolation($constraint::INITIATED_BY_REQUIRED)->atPath('initiatedBy')->addViolation();
        }

        if (TransactionTypeEnum::MINT === $transaction->getType()) {
            if ($transaction->getWalletFrom() instanceof Wallet) {
                $this->context->buildViolation($constraint::MINT_WALLET_FROM_FORBIDDEN)->atPath('walletFrom')->addViolation();
            }
            if (!$transaction->getWalletTo() instanceof Wallet || WalletTypeEnum::BANK !== $transaction->getWalletTo()->getType()) {
                $this->context->buildViolation($constraint::MINT_WRONG_WALLET_TO)->atPath('walletTo')->addViolation();
            }

            return;
        }

        if ($transaction->getWalletTo() instanceof Wallet) {
            $this->context->buildViolation($constraint::BURN_WALLET_TO_FORBIDDEN)->atPath('walletTo')->addViolation();
        }
        if (!$transaction->getWalletFrom() instanceof Wallet || WalletTypeEnum::BANK !== $transaction->getWalletFrom()->getType()) {
            $this->context->buildViolation($constraint::BURN_WRONG_WALLET_FROM)->atPath('walletFrom')->addViolation();

            return;
        }
        if (!$this->hasEnoughCoins($transaction)) {
            $this->context->buildViolation($constraint::NOT_ENOUGH_CURRENCY_IN_WALLET)->atPath('walletFrom')->addViolation();
        }
    }

    private function validateReason(Transaction $transaction, TransactionConstraint $constraint): void
    {
        $reason = $transaction->getReason();
        if (!\is_string($reason) || mb_strlen(trim($reason)) < 3 || mb_strlen($reason) > 500) {
            $this->context->buildViolation($constraint::REASON_REQUIRED)->atPath('reason')->addViolation();
        }
    }

    private function validateSameWallet(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if ($transaction->getWalletFrom() === $transaction->getWalletTo()) {
            $this->context->buildViolation($constraint::SAME_WALLET_FOR_TRANSACTION)
                ->addViolation()
            ;
        }
    }

    private function validateEnoughCoins(Transaction $transaction, TransactionConstraint $constraint): void
    {
        if (!$this->hasEnoughCoins($transaction)) {
            $this->context->buildViolation($constraint::NOT_ENOUGH_CURRENCY_IN_WALLET)
                ->addViolation()
            ;
        }
    }

    private function hasEnoughCoins(Transaction $value): bool
    {
        $balance = (string) $value->getWalletFrom()?->getAmount();
        $amount = (string) $value->getAmount();

        return is_numeric($balance) && is_numeric($amount) && bccomp($balance, $amount) >= 0;
    }

    private function validateAirDropType(Transaction $transaction, TransactionConstraint $constraint, Wallet $walletFrom): void
    {
        if (
            TransactionTypeEnum::AIR_DROP === $transaction->getType()
            && WalletTypeEnum::BANK !== $walletFrom->getType()
        ) {
            $this->context->buildViolation($constraint::AIR_DROP_WRONG_WALLET_FROM)
                ->addViolation()
            ;
        }
    }

    private function validateRegulationType(Transaction $transaction, TransactionConstraint $constraint, Wallet $walletFrom, Wallet $walletTo): void
    {
        if (
            TransactionTypeEnum::REGULATION === $transaction->getType()
            && (WalletTypeEnum::BANK !== $walletFrom->getType()
                && WalletTypeEnum::BANK !== $walletTo->getType())
        ) {
            $this->context->buildViolation($constraint::REGULATION_NO_BANK_WALLET)
                ->addViolation()
            ;
        }
    }

    private function validateWelcomeBonusType(Transaction $transaction, TransactionConstraint $constraint, Wallet $walletFrom, Wallet $walletTo): void
    {
        if (
            TransactionTypeEnum::WELCOME_BONUS === $transaction->getType()
            && (WalletTypeEnum::BANK !== $walletFrom->getType()
                || WalletTypeEnum::USER !== $walletTo->getType())
        ) {
            $this->context->buildViolation($constraint::WELCOME_BONUS_WRONG_WALLETS)
                ->addViolation()
            ;
        }
    }

    private function validateAppTypes(Transaction $transaction, TransactionConstraint $constraint, Wallet $walletFrom, Wallet $walletTo): void
    {
        $playerToBank = WalletTypeEnum::USER === $walletFrom->getType() && WalletTypeEnum::BANK === $walletTo->getType();
        $bankToPlayer = WalletTypeEnum::BANK === $walletFrom->getType() && WalletTypeEnum::USER === $walletTo->getType();

        $violation = match ($transaction->getType()) {
            TransactionTypeEnum::PURCHASE => $playerToBank ? null : $constraint::PURCHASE_WRONG_WALLETS,
            TransactionTypeEnum::MARKET_PAYMENT => $playerToBank ? null : $constraint::MARKET_PAYMENT_WRONG_WALLETS,
            TransactionTypeEnum::REWARD => $bankToPlayer ? null : $constraint::REWARD_WRONG_WALLETS,
            TransactionTypeEnum::MARKET_PAYOUT => $bankToPlayer ? null : $constraint::MARKET_PAYOUT_WRONG_WALLETS,
            TransactionTypeEnum::MARKET_REFUND => $bankToPlayer ? null : $constraint::MARKET_REFUND_WRONG_WALLETS,
            default => null,
        };

        if (null !== $violation) {
            $this->context->buildViolation($violation)->addViolation();
        }
    }
}
