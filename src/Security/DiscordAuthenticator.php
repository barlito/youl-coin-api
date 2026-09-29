<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Service\Util\TargetPathRouter;
use App\Service\Wallet\UserWalletFactory;
use App\Service\WelcomeBonus\WelcomeBonusService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DiscordAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly DiscordUserWhitelist $discordUserWhitelist,
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpUtils $httpUtils,
        private readonly TargetPathRouter $targetPathRouter,
        private readonly RouterInterface $router,
        private readonly AuthenticationSuccessHandler $jwtAuthSuccessHandler,
        private readonly LoggerInterface $logger,
        private readonly WelcomeBonusService $welcomeBonusService,
        private readonly UserWalletFactory $userWalletFactory,
    ) {
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
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
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client): DiscordUser {
                /** @var DiscordResourceOwner $discordUser */
                $discordUser = $client->fetchUserFromToken($accessToken);

                if (!$this->discordUserWhitelist->isAllowed((string) $discordUser->getId())) {
                    throw new AuthenticationException('Your account is not allowed to access this app.');
                }

                $user = $this->entityManager->getRepository(DiscordUser::class)->find($discordUser->getId())
                    ?? $this->createDiscordUser($discordUser);

                // Also covers accounts created before wallets were provisioned at login
                $hasPersistedWallet = $user->getWallet() instanceof Wallet || $this->createWallet($user);
                if ($hasPersistedWallet && $this->entityManager->isOpen()) {
                    $this->welcomeBonusService->grantIfEligible($user);
                }

                return $user;
            }),
            [new RememberMeBadge()],
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $response = new RedirectResponse($this->targetPathRouter->determineTargetUrl($request, $firewallName));
        $jwtResponse = $this->jwtAuthSuccessHandler->onAuthenticationSuccess($request, $token);

        foreach ($jwtResponse->headers->getCookies() as $cookie) {
            $response->headers->setCookie($cookie);
        }

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
        $user = new DiscordUser()
            ->setDiscordId($discordUser->getId())
            ->setUsername($discordUser->getUsername())
            ->setRoles([RoleEnum::ROLE_USER->value])
        ;
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createWallet(DiscordUser $user): bool
    {
        // This login also signs players into youl-tcg: a failed wallet must never block it, the next login retries
        try {
            $this->entityManager->persist($this->userWalletFactory->create($user));
            $this->entityManager->flush();

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Wallet creation at login failed.', [
                'discordId' => $user->getDiscordId(),
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
