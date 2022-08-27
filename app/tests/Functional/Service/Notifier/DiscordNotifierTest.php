<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Notifier;

use App\Money\YoulCoinFormatter;
use App\Repository\TransactionRepository;
use App\Service\Notifier\DiscordNotifier;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DiscordNotifierTest extends KernelTestCase
{
    public function testNewTransactionShouldLogOnError()
    {
        $chatterMock = $this->createMock(ChatterInterface::class);
        $chatterMock->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('test failed', $this->createMock(ResponseInterface::class)))
        ;

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('critical')
            ->with('test failed')
        ;

        $discordNotifier = new DiscordNotifier(
            $chatterMock,
            $loggerMock,
            $this->getContainer()->get(YoulCoinFormatter::class),
            $this->getContainer()->getParameter('app.discord'),
        );
        $discordNotifier->notifyNewTransaction($this->getContainer()->get(TransactionRepository::class)->findAll()[0]);
    }

    public function testErrorOnTransactionShouldLogOnError()
    {
        $chatterMock = $this->createMock(ChatterInterface::class);
        $chatterMock->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('test failed', $this->createMock(ResponseInterface::class)))
        ;

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('critical')
            ->with('test failed')
        ;

        $discordNotifier = new DiscordNotifier(
            $chatterMock,
            $loggerMock,
            $this->getContainer()->get(YoulCoinFormatter::class),
            $this->getContainer()->getParameter('app.discord'),
        );
        $discordNotifier->notifyErrorOnTransaction('test failed', 'test failed');
    }
}
