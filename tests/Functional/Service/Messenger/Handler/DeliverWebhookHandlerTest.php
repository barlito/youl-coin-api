<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\DeliverWebhook;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Handler\DeliverWebhookHandler;
use App\Service\Webhook\WebhookSigner;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class DeliverWebhookHandlerTest extends KernelTestCase
{
    // Farph (700000000000) to Barlito (900000000000), 2500000000 units
    private const string PLAYER_TRANSACTION_ID = 'a1b2c3d4-0000-4000-8000-000000000001';

    private const array SUBSCRIBERS = [['name' => 'ytcg', 'url' => 'http://ytcg/webhooks/youl-coin', 'secret' => 'shared-secret']];

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');
    }

    public function testItPostsTheSignedPayloadWithTheCurrentPlayerBalances(): void
    {
        $requests = [];
        $this->handler(self::SUBSCRIBERS, $requests)(new DeliverWebhook(self::PLAYER_TRANSACTION_ID, 'ytcg'));

        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertSame('POST', $request['method']);
        $this->assertSame('http://ytcg/webhooks/youl-coin', $request['url']);

        $transaction = $this->transaction(self::PLAYER_TRANSACTION_ID);
        $this->assertSame(
            [
                'event' => 'transaction.committed',
                'transactionId' => self::PLAYER_TRANSACTION_ID,
                'type' => 'classic',
                'amount' => '2500000000',
                'description' => null,
                'createdAt' => \DateTimeImmutable::createFromInterface($transaction->getCreatedAt())->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM),
                'wallets' => [
                    ['discordId' => '188967949963362304', 'balance' => '700000000000'],
                    ['discordId' => '188967649332428800', 'balance' => '900000000000'],
                ],
            ],
            json_decode($request['body'], true, flags: \JSON_THROW_ON_ERROR),
        );
        $this->assertTrue(new WebhookSigner()->verify(
            $request['headers'][strtolower(WebhookSigner::SIGNATURE_HEADER)],
            $request['body'],
            'shared-secret',
            (int) $request['headers'][strtolower(WebhookSigner::TIMESTAMP_HEADER)],
        ));
    }

    public function testANon2xxResponseThrowsSoThatTheOutboxRetries(): void
    {
        $this->expectException(HttpExceptionInterface::class);

        $requests = [];
        $this->handler(self::SUBSCRIBERS, $requests, 500)(new DeliverWebhook(self::PLAYER_TRANSACTION_ID, 'ytcg'));
    }

    public function testAnUnknownSubscriberIsNotRetried(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        $requests = [];
        $this->handler(self::SUBSCRIBERS, $requests)(new DeliverWebhook(self::PLAYER_TRANSACTION_ID, 'gone'));
    }

    /**
     * @param list<array{name: string, url: string, secret: string}> $subscribers
     * @param list<array<string, mixed>>                             $requests
     */
    private function handler(array $subscribers, array &$requests, int $status = 204): DeliverWebhookHandler
    {
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests, $status): MockResponse {
            $headers = [];
            foreach ($options['headers'] as $header) {
                [$name, $value] = explode(': ', $header, 2);
                $headers[strtolower($name)] = $value;
            }
            $requests[] = ['method' => $method, 'url' => $url, 'body' => $options['body'], 'headers' => $headers];

            return new MockResponse('', ['http_code' => $status]);
        });

        return new DeliverWebhookHandler(static::getContainer()->get(TransactionRepository::class), $client, new WebhookSigner(), $subscribers);
    }

    private function transaction(string $id): Transaction
    {
        $transaction = static::getContainer()->get(TransactionRepository::class)->find($id);
        $this->assertInstanceOf(Transaction::class, $transaction);

        return $transaction;
    }
}
