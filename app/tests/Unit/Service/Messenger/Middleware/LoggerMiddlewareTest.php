<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Middleware;

use App\Message\TransactionMessage;
use App\Service\Messenger\Middleware\LoggerMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpSender;

class LoggerMiddlewareTest extends TestCase
{
    public function testHandleLoggerMessageMiddlewareWithReceivedMessage()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with('Received {class}', ['class' => 'App\Message\TransactionMessage'])
        ;

        $loggerMiddleware = (new LoggerMiddleware($loggerMock));

        $envelope = new Envelope((new TransactionMessage()), [new ReceivedStamp('testTransport')]);

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

    public function testHandleLoggerMessageMiddlewareWithSentMessage()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())
            ->method('info')
        ;

        $loggerMiddleware = (new LoggerMiddleware($loggerMock));

        $envelope = new Envelope((new TransactionMessage()), [new SentStamp(AmqpSender::class)]);

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
