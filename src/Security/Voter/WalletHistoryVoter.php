<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Enum\Roles\ApiUserRoleEnum;
use App\Security\PlayerTokenResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, string>
 */
class WalletHistoryVoter extends Voter
{
    public const string READ = 'WALLET_HISTORY_READ';

    public function __construct(private readonly PlayerTokenResolver $playerTokenResolver)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::READ === $attribute && \is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $roles = $token->getRoleNames();

        if (\in_array(ApiUserRoleEnum::ROLE_WALLET_HISTORY_READ_ANY->value, $roles, true)) {
            return true;
        }

        if (!\in_array(ApiUserRoleEnum::ROLE_WALLET_HISTORY_READ->value, $roles, true)) {
            return false;
        }

        return $subject === $this->playerTokenResolver->resolveDiscordId();
    }
}
