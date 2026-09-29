<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\RedirectTargetPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedirectTargetPolicyTest extends TestCase
{
    private const array ALLOWED_HOSTS = ['youlz.fr', '*.youlz.fr', 'barlito.fr', '*.barlito.fr', '*.local.barlito.fr'];

    #[DataProvider('provideTargets')]
    public function testSanitize(?string $target, ?string $expected): void
    {
        $policy = new RedirectTargetPolicy(self::ALLOWED_HOSTS);

        $this->assertSame($expected, $policy->sanitize($target));
    }

    /**
     * @return iterable<string, array{0: ?string, 1: ?string}>
     */
    public static function provideTargets(): iterable
    {
        yield 'root path' => ['/', '/'];
        yield 'admin path' => ['/admin', '/admin'];
        yield 'path with query string' => ['/collection?foo=bar', '/collection?foo=bar'];
        yield 'protocol-relative is refused' => ['//evil.com', null];
        yield 'backslash is refused' => ['/\\evil.com', null];
        yield 'allowed absolute https url' => ['https://ytcg.youlz.fr/x', 'https://ytcg.youlz.fr/x'];
        yield 'host is compared case-insensitively' => ['https://YTCG.youlz.fr', 'https://YTCG.youlz.fr'];
        yield 'unknown host is refused' => ['https://evil.com', null];
        yield 'lookalike host is refused' => ['https://youlz.fr.evil.com', null];
        yield 'userinfo is refused' => ['https://user@evil.com', null];
        yield 'userinfo on an otherwise allowed host is refused' => ['https://user@ytcg.youlz.fr', null];
        yield 'unknown scheme is refused' => ['javascript:alert(1)', null];
        yield 'http is refused' => ['http://ytcg.youlz.fr', null];
        yield 'fragment trick towards an unknown host is refused' => ['https://evil.com#@ytcg.youlz.fr', null];
        yield 'encoded slash before userinfo is refused' => ['https://ytcg.youlz.fr%2F@evil.com', null];
        yield 'trailing dot host is refused' => ['https://ytcg.youlz.fr.', null];
        yield 'explicit port is refused' => ['https://ytcg.youlz.fr:8443/x', null];
        yield 'two-level subdomain allowed by its own wildcard' => ['https://ytcg.local.barlito.fr', 'https://ytcg.local.barlito.fr'];
        yield 'null target' => [null, null];
        yield 'empty target' => ['', null];
        yield 'control character is refused' => ["/foo\x00bar", null];
        yield 'whitespace is refused' => ['/foo bar', null];
    }

    public function testHostsAreNormalized(): void
    {
        $policy = new RedirectTargetPolicy([' YOULZ.fr ', '', '*.Youlz.fr']);

        $this->assertSame('https://youlz.fr', $policy->sanitize('https://youlz.fr'));
        $this->assertSame('https://ytcg.youlz.fr', $policy->sanitize('https://ytcg.youlz.fr'));
    }

    public function testSingleLevelWildcardDoesNotCoverATwoLevelSubdomain(): void
    {
        $hostsWithoutTheTwoLevelWildcard = ['youlz.fr', '*.youlz.fr', 'barlito.fr', '*.barlito.fr'];
        $policy = new RedirectTargetPolicy($hostsWithoutTheTwoLevelWildcard);

        $this->assertNull($policy->sanitize('https://ytcg.local.barlito.fr'));
    }
}
