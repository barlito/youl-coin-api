<?php

declare(strict_types=1);

namespace App\Service\Subscriber\Serializer;

use App\Message\TransactionMessage;
use App\Repository\DiscordUserRepository;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

class TransactionMessageSerializerHandler implements SubscribingHandlerInterface
{
    public function __construct(
        private DiscordUserRepository $discordUserRepository,
    ) {
    }

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
        // TODO log the JSON data
        if (\is_array($data)) {
            $this->addWallets($data);
        }

        return $this->buildObject($data);
    }

    private function addWallets(array &$data): array
    {
        if (isset($data['discordUserIdFrom'])) {
            $discordUserFrom = $this->discordUserRepository->find($data['discordUserIdFrom']);
            if (null !== $discordUserFrom) {
                $data['walletFrom'] = $discordUserFrom->getWallet();
            }
        }

        if (isset($data['discordUserIdTo'])) {
            $discordUserTo = $this->discordUserRepository->find($data['discordUserIdTo']);
            if (null !== $discordUserTo) {
                $data['walletTo'] = $discordUserTo->getWallet();
            }
        }

        return $data;
    }

    private function buildObject(array $data): TransactionMessage
    {
        return new TransactionMessage(
            (string) $data['amount'] ?? null,
            $data['walletFrom'] ?? null,
            $data['walletTo'] ?? null,
            $data['type'] ?? null,
            $data['message'] ?? null,
        );
    }
}
