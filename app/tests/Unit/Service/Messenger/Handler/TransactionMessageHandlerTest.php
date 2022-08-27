<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Messenger\Handler\TransactionMessageHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Errors already tested in the @App\Tests\Functional\Service\Messenger\Handler\TransactionMessageHandlerTest
 */
class TransactionMessageHandlerTest extends TestCase
{
    public function testTransactionMessageHandler()
    {
        $transactionMock = $this->createMock(Transaction::class);

        $transactionBuilderMock = $this->createMock(TransactionBuilder::class);
        $transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isInstanceOf(TransactionMessage::class))
            ->willReturn($transactionMock)
        ;

        $transactionHandlerMock = $this->createMock(TransactionHandler::class);
        $transactionHandlerMock->expects($this->once())
            ->method('handleTransaction')
            ->with($transactionMock)
        ;

        $transactionMessageHandler = $this->getTransactionMessageHandler($transactionBuilderMock, $transactionHandlerMock);

        $transactionMessageHandler($this->createMock(TransactionMessage::class));
    }

    private function getTransactionMessageHandler(TransactionBuilder $transactionBuilderMock, TransactionHandler $transactionHandlerMock): TransactionMessageHandler
    {
        return new TransactionMessageHandler(
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordNotifier::class),
            $this->createMock(SerializerInterface::class),
            $transactionBuilderMock,
            $transactionHandlerMock,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ValidatorInterface::class),
        );
    }
}
