<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Wallet;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiContext extends ApiTestCase implements Context
{
    private ResponseInterface $response;

    /**
     * @Given (I )send a :method request to :url
     */
    public function iSendARequestTo(string $method, string $url, PyStringNode $body = null, $files = []): void
    {
        $this->response = self::createClient()->request($method, $url);
    }

    /**
     * @Then (the )response status code should be :expected
     */
    public function assertResponseCode(int $expected): void
    {
        self::assertResponseStatusCodeSame($expected);
    }

    /**
     * @Given /^JSON schema should validate Wallet class$/
     */
    public function jsonNodeShouldExist(): void
    {
        self::assertMatchesResourceItemJsonSchema(Wallet::class);
    }

    /**
     * @Then /^the response should be in JSON$/
     */
    public function responseShouldBeInJson()
    {
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
}
