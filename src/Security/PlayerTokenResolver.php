<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingClaimException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\BlockedTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Reads the player JWT (issued by this app at Discord login) that an API client forwards
 * to prove the player is the one sending the coins.
 */
readonly class PlayerTokenResolver
{
    public const string HEADER = 'X-Player-Token';

    public function __construct(
        private RequestStack $requestStack,
        private JWTTokenManagerInterface $jwtManager,
        private BlockedTokenManagerInterface $blockedTokenManager,
    ) {
    }

    /**
     * Discord id of the forwarded player token, null when it is missing, invalid, expired or revoked.
     */
    public function resolveDiscordId(): ?string
    {
        $token = $this->requestStack->getCurrentRequest()?->headers->get(self::HEADER);
        if (null === $token || '' === $token) {
            return null;
        }

        try {
            $payload = $this->jwtManager->parse($token);
        } catch (JWTDecodeFailureException) {
            return null;
        }

        // The blocklist (logout) is only enforced by the JWT authenticator, which this header bypasses
        try {
            if ($this->blockedTokenManager->has($payload)) {
                return null;
            }
        } catch (MissingClaimException) {
        }

        $discordId = $payload['discordId'] ?? null;

        return \is_string($discordId) && '' !== $discordId ? $discordId : null;
    }
}
