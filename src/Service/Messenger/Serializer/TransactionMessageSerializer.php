<?php

declare(strict_types=1);

namespace App\Service\Messenger\Serializer;

use App\Message\TransactionMessage;
use Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransactionMessageSerializer implements SerializerInterface
{
    public function __construct(private \JMS\Serializer\SerializerInterface $serializer)
    {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $transactionMessage = $this->getTransactionMessageFromBody($body);

        return new Envelope($transactionMessage);
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function encode(Envelope $envelope): array
    {
        throw new Exception('Transport & serializer not meant for sending messages');
    }

    private function getTransactionMessageFromBody(string $body): TransactionMessage
    {
        return $this->serializer->deserialize($body, TransactionMessage::class, 'json');
    }
}
