<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Handler;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Messenger\Handler\TransactionMessageHandler;
use App\Service\Notifier\Transaction\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;

class TransactionMessageHandlerTest extends KernelTestCase
{
    //    use RecreateDatabaseTrait;
    //
    //    private ?Wallet $walletFrom = null;
    //    private ?Wallet $walletTo = null;
    //
    //    public function setUp(): void
    //    {
    //        $this->bootKernel();
    //    }
    //
    //    /** @dataProvider getErrorMessages */
    //    public function testTransactionErrorMessageValidation(TransactionMessage $transactionMessage, string $exceptionMessage)
    //    {
    //        $loggerMock = $this->createMock(LoggerInterface::class);
    //        $loggerMock->expects($this->once())
    //            ->method('critical')
    //        ;
    //
    //        $discordNotifierMock = $this->createMock(DiscordNotifier::class);
    //        $discordNotifierMock->expects($this->once())
    //            ->method('notifyErrorOnTransaction')
    //        ;
    //
    //        $transactionMessageHandler = $this->getTransactionMessageHandler(logger: $loggerMock, discordNotifierMock: $discordNotifierMock);
    //        $this->expectException(ConstraintDefinitionException::class);
    //        $this->expectExceptionMessage($exceptionMessage);
    //
    //        $transactionMessageHandler($transactionMessage);
    //    }
    //
    //    private function getErrorMessages(): array
    //    {
    //        return
    //            [
    //                [
    //                    new TransactionMessage(null, '8c2cc69c-2acf-415f-9815-b1e6a607bb59', '7f0e0f4f-e088-4edd-9fb5-cf51aeec1482', TransactionTypeEnum::CLASSIC),
    //                    'The amount value should not be blank.',
    //                ],
    //                [
    //                    new TransactionMessage('-300', '8c2cc69c-2acf-415f-9815-b1e6a607bb59', '7f0e0f4f-e088-4edd-9fb5-cf51aeec1482', TransactionTypeEnum::CLASSIC),
    //                    'The amount value is not a positive integer',
    //                ],
    //                [
    //                    new TransactionMessage('', '8c2cc69c-2acf-415f-9815-b1e6a607bb59', '7f0e0f4f-e088-4edd-9fb5-cf51aeec1482', TransactionTypeEnum::CLASSIC),
    //                    'The amount value should not be blank.',
    //                ],
    //                [
    //                    new TransactionMessage('10', '8c2cc69c-2acf-415f-9815-b1e6a607bb59', '7f0e0f4f-e088-4edd-9fb5-cf51aeec1482', null),
    //                    'The type value should not be null.',
    //                ],
    //                [
    //                    new TransactionMessage('10', '8c2cc69c-2acf-415f-9815-b1e6a607bb59', '7f0e0f4f-e088-4edd-9fb5-cf51aeec1482', 'wrong'),
    //                    'The type value you selected is not a valid choice.',
    //                ],
    //            ];
    //    }
    //
    //    private function getTransactionMessageHandler(
    //        LoggerInterface $logger = null,
    //        DiscordNotifier $discordNotifierMock = null,
    //    ): TransactionMessageHandler {
    //        return new TransactionMessageHandler(
    //            $logger ?? $this->createMock(LoggerInterface::class),
    //            $discordNotifierMock ?? $this->createMock(DiscordNotifier::class),
    //            $this->createMock(SerializerInterface::class),
    //            $this->getContainer()->get(TransactionBuilder::class),
    //            $this->getContainer()->get(TransactionHandler::class),
    //            $this->createMock(EntityManagerInterface::class),
    //            Validation::createValidatorBuilder()
    //                ->enableAnnotationMapping()
    //                ->addDefaultDoctrineAnnotationReader()
    //                ->getValidator(),
    //        );
    //    }
}
