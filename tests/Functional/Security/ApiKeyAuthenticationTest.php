<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\ApiUser;
use App\Repository\ApiUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiKeyAuthenticationTest extends WebTestCase
{
    public function testAWrongApiKeyIsRejected(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $client = static::createClient();
        $client->request('GET', '/api/transactions', server: ['HTTP_AUTHORIZATION' => 'Bearer not_a_key']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testTheHashOfAKeyIsNotTheKeyItself(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $apiUser = static::getContainer()->get(ApiUserRepository::class)->findOneBy(['name' => 'test']);
        $this->assertInstanceOf(ApiUser::class, $apiUser);

        $row = static::getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAssociative('SELECT * FROM api_user WHERE id = ?', [$apiUser->getId()])
        ;
        $this->assertIsArray($row);
        $this->assertSame(hash('sha256', 'api_key_test'), $row['api_key_hash']);
        $this->assertSame('api_ke', $row['api_key_prefix']);
        $this->assertNotContains('api_key_test', $row);
        $this->assertArrayNotHasKey('api_key', $row);
    }
}
