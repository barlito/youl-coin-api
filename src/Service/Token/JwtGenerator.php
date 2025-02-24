<?php
declare(strict_types=1);

namespace App\Service\Token;


use App\Entity\DiscordUser;
use App\Service\Util\TargetPathRouter;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtGenerator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $JWTManager,
    )
    {
    }

    # 15 minutes token
    public function generateCookie(?UserInterface $user, $payload = []): Cookie
    {
        if($user === null) {
            throw new \InvalidArgumentException('User cannot be null, this should not happen.');
        }

        if($user instanceof DiscordUser) {
            $payload = ['discordId' => $user->getDiscordId()];
        }

        $jwtToken = $this->JWTManager->createFromPayload($user, $payload);
        return Cookie::create('jwt', $jwtToken, time() + 900, '/', '.barlito.fr', true, true, sameSite: Cookie::SAMESITE_LAX);
    }
}
