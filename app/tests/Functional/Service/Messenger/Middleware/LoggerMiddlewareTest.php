<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Middleware;

use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Service\Messenger\Middleware\LoggerMiddleware;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Serializer\SerializerInterface;

class LoggerMiddlewareTest extends KernelTestCase
{
    /**
     * Verify the message content
     * It should log the message only if it's a Received message
     */
    public function testHandleLoggerMessageMiddlewareWithReceivedMessage()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Received {class}',
                [
                    'class' => 'App\Message\TransactionMessage',
                    'message' => '{"amount":"10","discordUserIdFrom":null,"discordUserIdTo":null,"type":"classic","externalIdentifier":"a84d8473-26b4-46e7-b184-19cf4751ff28"}',
                ],
            )
        ;

        $loggerMiddleware = (new LoggerMiddleware($loggerMock, $this->getContainer()->get(SerializerInterface::class)));

        $envelope = new Envelope(
            $this->getMessage(),
            [new ReceivedStamp('testTransport')],
        );

        $middlewareMock = $this->createMock(MiddlewareInterface::class);
        $middlewareMock->expects($this->once())
            ->method('handle')
            ->willReturn($envelope)
        ;

        $stackMock = $this->createMock(StackInterface::class);
        $stackMock->expects($this->once())
            ->method('next')
            ->willReturn($middlewareMock)
        ;

        $loggerMiddleware->handle($envelope, $stackMock);
    }

    private function getMessage(): TransactionMessage
    {
        return new TransactionMessage(
            amount : '10',
            type   : TransactionTypeEnum::CLASSIC,
            externalIdentifier: 'a84d8473-26b4-46e7-b184-19cf4751ff28',
        );
    }
}
