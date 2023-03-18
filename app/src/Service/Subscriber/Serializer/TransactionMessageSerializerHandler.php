<?php

declare(strict_types=1);

namespace App\Service\Subscriber\Serializer;

use App\Message\TransactionMessage;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

class TransactionMessageSerializerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => TransactionMessage::class,
                'method' => 'deserializeTransactionMessageFromJson',
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function deserializeTransactionMessageFromJson(DeserializationVisitorInterface $visitor, mixed $data, array $type, Context $context): TransactionMessage
    {
        return new TransactionMessage(
            (string) $data['amount'] ?? null,
            $data['discordUserIdFrom'] ?? null,
            $data['discordUserIdTo'] ?? null,
            $data['type'] ?? null,
            $data['message'] ?? null,
        );
    }
}
