<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\ApiUserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly ApiUserRepository $apiUserRepository)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $apiUser = $this->apiUserRepository->findOneBy(['apiKey' => $accessToken]);

        if (!$apiUser) {
            throw new BadCredentialsException();
        }

        return new UserBadge($apiUser->getUserIdentifier());
    }
}
