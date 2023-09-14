<?php

declare(strict_types=1);

namespace App\Service\Messenger\Serializer;

use App\Message\TransactionMessage;
use Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransactionMessageSerializer implements SerializerInterface
{
    public function __construct(
        private readonly \JMS\Serializer\SerializerInterface $serializer,
        private readonly string $env,
    ) {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $transactionMessage = $this->getTransactionMessageFromBody($body);

        return new Envelope($transactionMessage);
    }

    /**
     * @throws Exception
     *
     * @codeCoverageIgnore
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function encode(Envelope $envelope): array
    {
        // here use a service decorating this one
        if ('test' === $this->env) {
            /** @var TransactionMessage $message */
            $message = $envelope->getMessage();

            return [
                'body' => json_encode([
                    'amount' => $message->getAmount(),
                    'discordUserIdFrom' => $message->getDiscordUserIdFrom(),
                    'discordUserIdTo' => $message->getDiscordUserIdTo(),
                    'type' => $message->getType(),
                    'message' => $message->getMessage(),
                ]),
            ];
        }

        throw new Exception('Transport & serializer not meant for sending messages');
    }

    private function getTransactionMessageFromBody(string $body): TransactionMessage
    {
        //todo no need JMS here denormalizer from SF serializer component should do the job
        return $this->serializer->deserialize($body, TransactionMessage::class, 'json');
    }
}
