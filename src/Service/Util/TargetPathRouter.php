<?php

declare(strict_types=1);

namespace App\Service\Util;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TargetPathRouter
{
    use TargetPathTrait;

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function determineTargetUrl(Request $request, string $firewallName): string
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $firewallName);

            return $targetPath;
        }

        return $this->router->generate('admin');
    }
}
