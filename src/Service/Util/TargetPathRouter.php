<?php

declare(strict_types=1);

namespace App\Service\Util;

use App\Security\RedirectTargetPolicy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TargetPathRouter
{
    use TargetPathTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly RedirectTargetPolicy $redirectTargetPolicy,
    ) {
    }

    public function determineTargetUrl(Request $request, string $firewallName): string
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $firewallName);

            // Defense in depth: the session may hold a target saved before this check existed
            $sanitizedTargetPath = $this->redirectTargetPolicy->sanitize($targetPath);

            if (null !== $sanitizedTargetPath) {
                return $sanitizedTargetPath;
            }
        }

        return $this->router->generate('homepage');
    }
}
