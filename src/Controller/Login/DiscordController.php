<?php

declare(strict_types=1);

namespace App\Controller\Login;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DiscordController extends AbstractController
{
    use TargetPathTrait;

    /**
     * Link to this controller to start the "connect" process
     */
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connectAction(
        #[Autowire('@security.firewall.map')]
        FirewallMap $firewallMap,
        Request $request,
        ClientRegistry $clientRegistry
    ): RedirectResponse
    {
        $firewallName = $firewallMap->getFirewallConfig($request)?->getName();
        $targetUrl = $request->get('_target_path');

        if (\is_string($targetUrl) && (str_starts_with($targetUrl, '/') || str_starts_with($targetUrl, 'http'))) {
            $this->saveTargetPath($request->getSession(), $firewallName ?? 'main', $targetUrl);
        }

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

    #[Route('/test', name: 'test')]
    #[IsGranted('ROLE_USER')]
    public function test(): Response
    {
        return $this->render('base.html.twig');
    }
}
