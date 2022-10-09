<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Middleware;

use App\Message\TransactionMessage;
use App\Service\Messenger\Middleware\LoggerMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpSender;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Serializer\SerializerInterface;

class LoggerMiddlewareTest extends TestCase
{
    /**
     * It should log the message only if it's a Received message
     */
    public function testHandleLoggerMessageMiddlewareWithReceivedMessage()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with('Received {class}', ['class' => 'App\Message\TransactionMessage', 'message' => ''])
        ;

        $loggerMiddleware = (new LoggerMiddleware($loggerMock, $this->createMock(SerializerInterface::class)));

        $envelope = new Envelope(new TransactionMessage(), [new ReceivedStamp('testTransport')]);

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

    /**
     * It should log nothing because the message is not a Received one
     */
    public function testHandleLoggerMessageMiddlewareWithSentMessage()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())
            ->method('info')
        ;

        $loggerMiddleware = (new LoggerMiddleware($loggerMock, $this->createMock(SerializerInterface::class)));

        $envelope = new Envelope(new TransactionMessage(), [new SentStamp(AmqpSender::class)]);

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
}
