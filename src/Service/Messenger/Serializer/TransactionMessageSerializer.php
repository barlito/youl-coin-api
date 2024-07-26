<?php

declare(strict_types=1);

namespace App\Service\Messenger\Serializer;

use App\Message\TransactionMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializerInterface;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class TransactionMessageSerializer implements MessengerSerializerInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $transactionMessage = $this->getTransactionMessageFromBody($body);

        return new Envelope($transactionMessage);
    }

    /**
     * @throws \Exception
     *
     * @codeCoverageIgnore
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function encode(Envelope $envelope): array
    {
        throw new \RuntimeException('Transport & serializer not meant for sending messages');
    }

    private function getTransactionMessageFromBody(string $body): TransactionMessage
    {
        return $this->serializer->deserialize($body, TransactionMessage::class, 'json', [BackedEnumNormalizer::ALLOW_INVALID_VALUES => true, ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
    }
}
