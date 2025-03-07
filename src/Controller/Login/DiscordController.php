<?php

declare(strict_types=1);

namespace App\Controller\Login;

use App\Service\Util\TargetPathRouter;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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
    ): RedirectResponse {
        $firewallName = $security->getFirewallConfig($request)?->getName();
        $targetUrl = $request->get('_target_path');

        if (\is_string($targetUrl) && (str_starts_with($targetUrl, '/') || str_starts_with($targetUrl, 'http'))) {
            $this->saveTargetPath($request->getSession(), $firewallName ?? 'main', $targetUrl);
        }

        if ($this->getUser() instanceof UserInterface) {
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
