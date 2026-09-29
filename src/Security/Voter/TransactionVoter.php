<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Roles\ApiUserRoleEnum;
use App\Enum\WalletTypeEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Transaction>
 */
class TransactionVoter extends Voter
{
    public const string CREATE = 'TRANSACTION_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CREATE === $attribute && $subject instanceof Transaction;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $requiredRole = $this->requiredRole($subject);

        return $requiredRole instanceof ApiUserRoleEnum && \in_array($requiredRole->value, $token->getRoleNames(), true);
    }

    private function requiredRole(Transaction $transaction): ?ApiUserRoleEnum
    {
        $walletFrom = $transaction->getWalletFrom();
        $walletTo = $transaction->getWalletTo();

        if (!$walletFrom instanceof Wallet || !$walletTo instanceof Wallet) {
            return null;
        }

        return match ([$walletFrom->getType(), $walletTo->getType()]) {
            [WalletTypeEnum::BANK, WalletTypeEnum::USER] => ApiUserRoleEnum::ROLE_TRANSACTION_BANK_TO_USER,
            [WalletTypeEnum::USER, WalletTypeEnum::BANK] => ApiUserRoleEnum::ROLE_TRANSACTION_USER_TO_BANK,
            [WalletTypeEnum::USER, WalletTypeEnum::USER] => ApiUserRoleEnum::ROLE_TRANSACTION_USER_TO_USER,
            default => null,
        };
    }
}
