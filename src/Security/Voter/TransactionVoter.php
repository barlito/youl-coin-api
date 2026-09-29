<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Roles\ApiUserRoleEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Security\PlayerTokenResolver;
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

    public function __construct(private readonly PlayerTokenResolver $playerTokenResolver)
    {
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Mint/Burn are admin-only (bank wallet page): never reachable through the API, whatever the role
        if (\in_array($subject->getType(), [TransactionTypeEnum::MINT, TransactionTypeEnum::BURN], true)) {
            return false;
        }

        // Incomplete payload: nothing can move, let the handler answer 422 instead of a 403
        if (!$subject->getWalletFrom() instanceof Wallet || !$subject->getWalletTo() instanceof Wallet) {
            return true;
        }

        $requiredRole = $this->requiredRole($subject);

        if (!$requiredRole instanceof ApiUserRoleEnum || !\in_array($requiredRole->value, $token->getRoleNames(), true)) {
            return false;
        }

        return $this->isSentByTheWalletOwner($subject);
    }

    /**
     * Coins only leave a player's wallet on that player's behalf: the API client must forward their JWT.
     */
    private function isSentByTheWalletOwner(Transaction $transaction): bool
    {
        $walletFrom = $transaction->getWalletFrom();
        if (!$walletFrom instanceof Wallet || WalletTypeEnum::USER !== $walletFrom->getType()) {
            return true;
        }

        $ownerDiscordId = $walletFrom->getDiscordUser()?->getDiscordId();

        return null !== $ownerDiscordId && $ownerDiscordId === $this->playerTokenResolver->resolveDiscordId();
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
