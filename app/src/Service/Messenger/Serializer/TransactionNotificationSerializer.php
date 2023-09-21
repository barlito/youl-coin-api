<?php

declare(strict_types=1);

namespace App\Service\Messenger\Serializer;

use App\Entity\Transaction;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TransactionNotificationSerializer implements MessengerSerializerInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        throw new \RuntimeException('This app should not consume this type of messages.');
    }

    /**
     * @throws ExceptionInterface
     */
    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if (!$message instanceof Transaction) {
            throw new UnexpectedTypeException($message, Transaction::class);
        }

        return [
            'body' => $this->serializer->serialize($message, 'json', ['groups' => ['transaction:notification']]),
            'headers' => [
                'stamps' => serialize($envelope->all()),
            ],
        ];
    }
}
