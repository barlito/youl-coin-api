<?php

declare(strict_types=1);

namespace App\Validator\Entity\Wallet;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DiscordUserWalletExistValidator extends ConstraintValidator
{
    public function __construct(private readonly WalletRepository $walletRepository)
    {
    }

    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DiscordUserWalletExist) {
            throw new UnexpectedTypeException($constraint, DiscordUserWalletExist::class);
        }

        if (!\is_string($value)) {
            return;
        }

        $criteria = ['discordUser' => $value];
        if (WalletTypeEnum::BANK->value === $value) {
            $criteria = ['type' => WalletTypeEnum::BANK];
        }

        $wallet = $this->walletRepository->findOneBy($criteria);

        if ($wallet instanceof Wallet) {
            return;
        }

        $this->context->buildViolation($constraint::WALLET_NOT_FOUND)
            ->addViolation()
        ;
    }
}
