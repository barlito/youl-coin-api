<?php

declare(strict_types=1);

namespace App\Controller\Login;

use App\Entity\DiscordUser;
use App\Security\DiscordUserWhitelist;
use App\Security\NotAllowedResponseFactory;
use App\Security\RedirectTargetPolicy;
use App\Service\Util\TargetPathRouter;
use App\Service\Wallet\PlayerWalletProvisioner;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DiscordController extends AbstractController
{
    use TargetPathTrait;

    #[Route('/refresh_token', name: 'refresh_token')]
    public function refreshToken(
        Security $security,
        Request $request,
        AuthenticationSuccessHandler $jwtAuthSuccessHandler,
        TargetPathRouter $targetPathRouter,
        RedirectTargetPolicy $redirectTargetPolicy,
        DiscordUserWhitelist $discordUserWhitelist,
        NotAllowedResponseFactory $notAllowedResponseFactory,
        PlayerWalletProvisioner $playerWalletProvisioner,
    ): Response {
        $user = $this->getUser();

        // The user checker only runs at login: a session outliving the whitelist entry must be cut here
        if ($user instanceof DiscordUser && !$discordUserWhitelist->isAllowed($user->getDiscordId())) {
            $security->logout(false);

            return $notAllowedResponseFactory->create();
        }

        if ($user instanceof DiscordUser) {
            $playerWalletProvisioner->provision($user);
        }

        $firewallName = $security->getFirewallConfig($request)?->getName();
        $sanitizedTargetUrl = $redirectTargetPolicy->sanitize($request->query->getString('_target_path'));

        if (null !== $sanitizedTargetUrl) {
            $this->saveTargetPath($request->getSession(), $firewallName ?? 'main', $sanitizedTargetUrl);
        }

        if ($user instanceof UserInterface) {
            $response = new RedirectResponse($targetPathRouter->determineTargetUrl($request, $firewallName));
            $jwtResponse = $jwtAuthSuccessHandler->onAuthenticationSuccess($request, $security->getToken());

            foreach ($jwtResponse->headers->getCookies() as $cookie) {
                $response->headers->setCookie($cookie);
            }

            return $response;
        }

        return new RedirectResponse($this->generateUrl('connect_discord_start'));
    }

    /**
     * Link to this controller to start the "connect" process
     */
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connectAction(
        ClientRegistry $clientRegistry,
    ): RedirectResponse {
        return $clientRegistry
            ->getClient('discord')
            ->redirect([
                'identify', 'email',
            ])
        ;
    }

    /**
     * After going to Discord, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry): void
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)
    }

    /**
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    #[Route('/logout', name: 'admin_logout', methods: ['GET'])]
    public function logout(): never
    {
        // controller can be blank: it will never be called!

        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}
