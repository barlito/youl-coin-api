<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Admin;
use App\Entity\DiscordUser;
use App\Enum\Roles\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DiscordAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    use TargetPathTrait;

    public function __construct(
        #[Autowire(param: 'app.allowed_discord_users')]
        private readonly array $allowedDiscordUsers,
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly JWTTokenManagerInterface $JWTManager,
        private readonly HttpUtils $httpUtils,
    ) {
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('connect_discord_start'),
            Response::HTTP_TEMPORARY_REDIRECT,
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supports(Request $request): ?bool
    {
        return 'connect_discord_check' === $request->attributes->get('_route');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('discord');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var DiscordResourceOwner $discordUser */
                $discordUser = $client->fetchUserFromToken($accessToken);

                $existingUser = $this->entityManager->getRepository(DiscordUser::class)->find($discordUser->getId());

                if ($existingUser) {
                    return $existingUser;
                }

                return $this->createDiscordUser($discordUser);
            })
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $payload = [];
        if($user instanceof DiscordUser) {
            $payload = ['discordId' => $user->getDiscordId()];
        }
        $jwtToken = $this->JWTManager->createFromPayload($token->getUser(), $payload);
        $cookie = Cookie::create('jwt', $jwtToken, time() + 3600, '/', '.barlito.fr', true, true, sameSite: Cookie::SAMESITE_LAX);

        $response = new RedirectResponse($this->determineTargetUrl($request, $firewallName));
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
    }

    private function createDiscordUser(DiscordResourceOwner $discordUser): DiscordUser
    {
        if (!\in_array($discordUser->getId(), $this->allowedDiscordUsers)) {
            throw new AuthenticationException('Your account is not allowed to access this app.');
        }

        $user = (new DiscordUser())
            ->setDiscordId($discordUser->getId())
            ->setUsername($discordUser->getUsername())
            ->setRoles([RoleEnum::ROLE_USER->value])
        ;
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function determineTargetUrl(Request $request, string $firewallName): string
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $firewallName);

            return $targetPath;
        }

        return $this->router->generate('admin');
    }
}
