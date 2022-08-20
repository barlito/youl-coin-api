<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger\Handler;

use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Messenger\Handler\TransactionMessageHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;

/**
 * Errors already tested in the @App\Tests\Functional\Service\Messenger\Handler\TransactionMessageHandlerTest
 */
class TransactionMessageHandlerTest extends TestCase
{
    private function getTransactionMessageHandler(): TransactionMessageHandler
    {
        $transactionBuilderMock = $this->createMock(TransactionBuilder::class);
        $transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isInstanceOf(TransactionMessage::class))
        ;

        $transactionHandlerMock = $this->createMock(TransactionHandler::class);
        $transactionHandlerMock->expects($this->once())
            ->method('handleTransaction')
            ->with($this->isInstanceOf(TransactionMessage::class))
        ;

        return new TransactionMessageHandler(
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordNotifier::class),
            $this->createMock(SerializerInterface::class),
            $transactionBuilderMock,
            $transactionHandlerMock,
            $this->createMock(EntityManagerInterface::class),
            Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->addDefaultDoctrineAnnotationReader()
                ->getValidator(),
        );
    }
}
