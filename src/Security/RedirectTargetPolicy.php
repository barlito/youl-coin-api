<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

// Only a single-slash path or an https URL on an allowed host may be followed after login
readonly class RedirectTargetPolicy
{
    /**
     * @var list<string>
     */
    private array $allowedHosts;

    /**
     * @param list<string> $allowedHosts
     */
    public function __construct(
        #[Autowire(env: 'csv:ALLOWED_REDIRECT_HOSTS')]
        array $allowedHosts,
    ) {
        $normalizedHosts = array_map(static fn (string $host): string => strtolower(trim($host)), $allowedHosts);
        $this->allowedHosts = array_values(array_filter($normalizedHosts, static fn (string $host): bool => '' !== $host));
    }

    public function sanitize(?string $target): ?string
    {
        if (null === $target || '' === $target) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]|\s/', $target) || str_contains($target, '\\')) {
            return null;
        }

        if (str_starts_with($target, '/')) {
            return str_starts_with($target, '//') ? null : $target;
        }

        return $this->isAllowedAbsoluteUrl($target) ? $target : null;
    }

    private function isAllowedAbsoluteUrl(string $target): bool
    {
        $parts = parse_url($target);

        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host']) || 'https' !== strtolower($parts['scheme'])) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['port'])) {
            return false;
        }

        return $this->isAllowedHost($parts['host']);
    }

    private function isAllowedHost(string $host): bool
    {
        $host = strtolower($host);

        foreach ($this->allowedHosts as $pattern) {
            if (str_starts_with($pattern, '*.')) {
                // Wildcard covers exactly one subdomain level, like a TLS wildcard certificate
                $suffix = substr($pattern, 1);
                if (str_ends_with($host, $suffix)) {
                    $subdomain = substr($host, 0, -\strlen($suffix));
                    if ('' !== $subdomain && !str_contains($subdomain, '.')) {
                        return true;
                    }
                }
            } elseif ($host === $pattern) {
                return true;
            }
        }

        return false;
    }
}
