<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessToken;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

trait MocksDiscordOAuthTrait
{
    private function mockClientRegistry(DiscordResourceOwner $discordResource): void
    {
        $mockAccessToken = $this->createMock(AccessToken::class);
        $mockAccessToken->method('getToken')->willReturn('fake_access_token');

        $mockOAuthClient = $this->createMock(OAuth2Client::class);
        $mockOAuthClient->method('getAccessToken')->willReturn($mockAccessToken);
        $mockOAuthClient->method('fetchUserFromToken')->willReturn($discordResource);

        $mockClientRegistry = $this->createMock(ClientRegistry::class);
        $mockClientRegistry->method('getClient')->with('discord')->willReturn($mockOAuthClient);

        static::getContainer()->set(ClientRegistry::class, $mockClientRegistry);
    }
}
