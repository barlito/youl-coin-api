<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Serializer;

use App\Message\TransactionMessage;
use App\Service\Messenger\Serializer\TransactionMessageSerializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionMessageSerializerTest extends KernelTestCase
{
    public function testTransactionMessageIsSetInEnvelopeMessage()
    {
        $body = [
            'amount' => 10,
            'discordUserIdFrom' => '188967649332428800',
            'discordUserIdTo' => '195659530363731968',
            'type' => 'classic',
            'message' => 'test message',
        ];
        $encodedEnvelope = [
            'body' => json_encode($body),
        ];

        $envelope = $this->getContainer()->get(TransactionMessageSerializer::class)->decode($encodedEnvelope);
        /** @var TransactionMessage $message */
        $message = $envelope->getMessage();

        $this->assertEquals($message->getAmount(), $body['amount']);
        $this->assertEquals($message->getDiscordUserIdFrom(), $body['discordUserIdFrom']);
        $this->assertEquals($message->getDiscordUserIdTo(), $body['discordUserIdTo']);
        $this->assertEquals($message->getType()->value, $body['type']);
        $this->assertEquals($message->getMessage(), $body['message']);
    }
}
