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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function encode(Envelope $envelope): array
    {
        if ('test' === $this->env) {
            /** @var TransactionMessage $message */
            $message = $envelope->getMessage();

            return [
                'body' => json_encode([
                    'amount' => $message->getAmount(),
                    'discordUserIdFrom' => $message->getWalletFrom()->getDiscordUser()->getDiscordId(),
                    'discordUserIdTo' => $message->getWalletTo()->getDiscordUser()->getDiscordId(),
                    'type' => $message->getType(),
                    'message' => $message->getMessage(),
                ]),
            ];
        }

        throw new Exception('Transport & serializer not meant for sending messages');
    }

    private function getTransactionMessageFromBody(string $body): TransactionMessage
    {
        return $this->serializer->deserialize($body, TransactionMessage::class, 'json');
    }
}
