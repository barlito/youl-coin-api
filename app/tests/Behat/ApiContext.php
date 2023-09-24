<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Transaction;
use App\Entity\Wallet;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiContext extends ApiTestCase implements Context
{
    private ResponseInterface $response;

    private array $headers = [];

    /**
     * @Given I set header :key with value :value
     */
    public function iSetHeaderWithValue($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @Given (I )send a :method request to :url
     */
    public function iSendARequestTo(string $method, string $url, PyStringNode $body = null, $files = []): void
    {
        $this->response = self::createClient()->request($method, $url);
    }

    /**
     * @Given (I )send a :method request to :url with body:
     *
     * @throws \JsonException
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $body)
    {
        $this->response = self::createClient()->request(
            $method,
            $url,
            [
                'json' => json_decode($body->getRaw(), true, 512, JSON_THROW_ON_ERROR),
                'headers' => $this->headers,
            ],
        );
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
    public function jsonSchemaShouldValidateWallet(): void
    {
        self::assertMatchesResourceItemJsonSchema(Wallet::class);
    }

    /**
     * @Given /^JSON schema should validate Transaction class$/
     */
    public function jsonSchemaShouldValidateTransaction(): void
    {
        self::assertMatchesResourceItemJsonSchema(Transaction::class);
    }

    /**
     * @Then /^the response should be in JSON$/
     */
    public function responseShouldBeInJson()
    {
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    /**
     * @Then the JSON should contain a ConstraintViolationList with :message
     */
    public function theJSONShouldContainAConstraintViolationListWith($message)
    {
        self::assertJsonContains([
            'hydra:title' => 'An error occurred',
            'hydra:description' => $message,
        ]);
    }
}
