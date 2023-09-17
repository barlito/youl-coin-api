<?php

declare(strict_types=1);

namespace App\Tests\Behat\Mock\Messenger\Serializer;

use App\Entity\Transaction;
use App\Service\Messenger\Serializer\TransactionNotificationSerializer;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Serializer\SerializerInterface;

#[AsDecorator(decorates: TransactionNotificationSerializer::class)]
class TransactionNotificationSerializerMock implements \Symfony\Component\Messenger\Transport\Serialization\SerializerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly object $inner,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $transactionMessage = $this->serializer->deserialize($body, Transaction::class, 'json', ['groups' => ['transaction:notification']]);

        return new Envelope($transactionMessage);
    }

    public function encode(Envelope $envelope): array
    {
        return $this->inner->encode($envelope);
    }
}
