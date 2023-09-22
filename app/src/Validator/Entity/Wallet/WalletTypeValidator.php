<?php

declare(strict_types=1);

namespace App\Validator\Entity\Wallet;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class WalletTypeValidator extends ConstraintValidator
{
    public function __construct(private readonly WalletRepository $walletRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof WalletType) {
            throw new UnexpectedTypeException($constraint, WalletType::class);
        }

        if (!$value instanceof Wallet) {
            throw new UnexpectedTypeException($value, Wallet::class);
        }

        $bankWallet = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);

        if (
            !$bankWallet instanceof Wallet
            || $bankWallet->getId() === $value->getId()
        ) {
            return;
        }

        $this->context->buildViolation($constraint::UNIQUE_BANK_WALLET_ERROR)
            ->addViolation()
        ;
    }
}
