<?php

declare(strict_types=1);

namespace App\Validator\DTO;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BankWalletValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BankWallet) {
            throw new UnexpectedTypeException($constraint, BankWallet::class);
        }

        if (!$value instanceof BankWalletTransactionDTO) {
            throw new UnexpectedTypeException($constraint, BankWalletTransactionDTO::class);
        }

        if (
            !$value->getWalletFrom() instanceof Wallet
            || !$value->getWalletTo() instanceof Wallet
            || WalletTypeEnum::BANK === $value->getWalletFrom()->getType()
            || WalletTypeEnum::BANK === $value->getWalletTo()->getType()
        ) {
            return;
        }

        $this->context->buildViolation($constraint::NO_BANK_WALLET_FOUND)
            ->addViolation()
        ;
    }
}
