<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Subscriber\Serializer;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Message\TransactionMessage;
use App\Repository\DiscordUserRepository;
use App\Service\Subscriber\Serializer\TransactionMessageSerializerHandler;
use JMS\Serializer\Context;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransactionMessageSerializerHandlerTest extends WebTestCase
{
    public function testDeserializeTransactionMessageFromJson()
    {
        $discordUserRepository = $this->createMock(DiscordUserRepository::class);
        $discordUserRepository
            ->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                $discordUser = (new DiscordUser())
                    ->setDiscordId($id)
                ;
                $wallet = (new Wallet())->setDiscordUser($discordUser);

                return $discordUser->setWallet($wallet);
            })
        ;

        $messageSerializerHandler = $this->getMessageSerializerHandler($discordUserRepository);
        $data = [
            'amount' => '5',
            'discordUserIdFrom' => '188967649332428800',
            'discordUserIdTo' => '232457563910832129',
            'type' => 'classic',
            'message' => 'test',
        ];

        $visitor = $this->createMock(DeserializationVisitorInterface::class);
        $context = $this->createMock(Context::class);

        $transactionMessage = $messageSerializerHandler->deserializeTransactionMessageFromJson($visitor, $data, [], $context);

        $this->assertTrue($transactionMessage instanceof TransactionMessage);
    }

    private function getMessageSerializerHandler($discordUserRepository): TransactionMessageSerializerHandler
    {
        return new TransactionMessageSerializerHandler($discordUserRepository);
    }
}
