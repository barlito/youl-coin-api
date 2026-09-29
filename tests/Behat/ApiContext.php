<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Security\PlayerTokenResolver;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\BlockedTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Assert;
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
     * @Given I send the player token of :discordId
     */
    public function iSendThePlayerTokenOf(string $discordId): void
    {
        $this->headers[PlayerTokenResolver::HEADER] = $this->createPlayerToken($discordId);
    }

    /**
     * @Given I send an expired player token of :discordId
     */
    public function iSendAnExpiredPlayerTokenOf(string $discordId): void
    {
        $this->headers[PlayerTokenResolver::HEADER] = $this->createPlayerToken($discordId, ['exp' => time() - 60]);
    }

    /**
     * @Given the player token has been revoked
     */
    public function thePlayerTokenHasBeenRevoked(): void
    {
        $container = static::getContainer();
        $payload = $container->get(JWTTokenManagerInterface::class)->parse($this->headers[PlayerTokenResolver::HEADER]);
        $container->get(BlockedTokenManagerInterface::class)->add($payload);
    }

    /**
     * @Given (I )send a :method request to :url
     */
    public function iSendARequestTo(string $method, string $url, ?PyStringNode $body = null, $files = []): void
    {
        $this->response = self::createClient()->request($method, $url, ['headers' => array_filter($this->headers)]);
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
        // Named explicitly: an unnamed lookup would match Wallet's unrelated NotFoundAction Get operation first
        self::assertMatchesResourceItemJsonSchema(Wallet::class, '/user/{discord_user_id}/wallet.{_format}');
    }

    /**
     * @Given /^JSON schema should validate Transaction class$/
     */
    public function jsonSchemaShouldValidateTransaction(): void
    {
        self::assertMatchesResourceItemJsonSchema(Transaction::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createPlayerToken(string $discordId, array $payload = []): string
    {
        $container = static::getContainer();
        $player = $container->get(EntityManagerInterface::class)->find(DiscordUser::class, $discordId);
        Assert::assertInstanceOf(DiscordUser::class, $player);

        return $container->get(JWTTokenManagerInterface::class)->createFromPayload($player, $payload);
    }

    /**
     * @Then /^the response should be in JSON$/
     */
    public function responseShouldBeInJson()
    {
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    /**
     * @Then the JSON should contain:
     *
     * @throws \JsonException
     */
    public function theJsonShouldContain(PyStringNode $expected): void
    {
        self::assertJsonContains(json_decode($expected->getRaw(), true, 512, JSON_THROW_ON_ERROR));
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

    /**
     * @Then the JSON should not have the key :key
     */
    public function theJsonShouldNotHaveTheKey(string $key): void
    {
        Assert::assertFalse(
            $this->jsonTreeHasKey($this->response->toArray(false), $key),
            \sprintf('The JSON should not contain the key "%s".', $key),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function jsonTreeHasKey(array $data, string $key): bool
    {
        if (\array_key_exists($key, $data)) {
            return true;
        }

        foreach ($data as $value) {
            if (\is_array($value) && $this->jsonTreeHasKey($value, $key)) {
                return true;
            }
        }

        return false;
    }
}
