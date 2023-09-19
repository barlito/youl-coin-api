<?php

declare(strict_types=1);

namespace App\Service\Messenger\Serializer;

use App\Entity\Transaction;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TransactionNotificationSerializer implements MessengerSerializerInterface
{
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

        /**
         * Here I use JMS because with Wallet API endpoint ApiPlatform interfere with SF normalizer
         * So, until it's fix and ApiPlatform is able to skip null values, I need to use JMS
         */
        $serializeBuilder = SerializerBuilder::create();
        $serializeBuilder->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy());
        $serializer = $serializeBuilder->build();

        return [
            'body' => $serializer->serialize($message, 'json', SerializationContext::create()->setGroups(['transaction:notification'])),
            'headers' => [
                'stamps' => serialize($envelope->all()),
            ],
        ];
    }
}
