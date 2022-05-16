<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Handler;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Messenger\Handler\TransactionMessageHandler;
use App\Service\Notifier\DiscordNotifier;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;

class TransactionMessageHandlerTest extends KernelTestCase
{
    use RecreateDatabaseTrait;

    public function setUp(): void
    {
        $this->bootKernel();
    }

    /** @dataProvider getErrorMessages */
    public function testTransactionErrorMessageValidation(TransactionMessage $transactionMessage, string $exceptionMessage)
    {
        $transactionMessageHandler = $this->getTransactionMessageHandler();
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $transactionMessageHandler($transactionMessage);
    }

    private function getErrorMessages(): array
    {
        $walletRepository = $this->getContainer()->get('doctrine')->getRepository(Wallet::class);
        $walletFrom = $walletRepository->findOneBy(['id' => '01FPD1DHMWPV4BHJQ82TSJEBJC']);
        $walletTo = $walletRepository->findOneBy(['id' => '01FPD1DNKVFS5GGBPVXBT3YQ01']);

        return
            [
                [
                    new TransactionMessage(null, $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC),
                    'The amount value should not be blank.',
                ],
                [
                    new TransactionMessage('-300', $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC),
                    'The amount value is not a positive integer',
                ],
                [
                    new TransactionMessage('', $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC),
                    'The amount value should not be blank.',
                ],
                [
                    new TransactionMessage('9999999', $walletFrom, $walletTo, TransactionTypeEnum::CLASSIC),
                    'Not enough coins in from wallet.',
                ],
                [
                    new TransactionMessage('10', null, $walletFrom, TransactionTypeEnum::CLASSIC),
                    'The walletFrom value should not be null.',
                ],
                [
                    new TransactionMessage('10', $walletFrom, null, TransactionTypeEnum::CLASSIC),
                    'The walletTo value should not be null.',
                ],
                [
                    new TransactionMessage('10', $walletFrom, $walletFrom, TransactionTypeEnum::CLASSIC),
                    'WalletFrom and WalletTo are the same.',
                ],
                [
                    new TransactionMessage('10', $walletFrom, $walletTo, null),
                    'The type value should not be null.',
                ],
                [
                    new TransactionMessage('10', $walletFrom, $walletTo, 'wrong'),
                    'The type value you selected is not a valid choice.',
                ],
            ];
    }

    private function getTransactionMessageHandler(): TransactionMessageHandler
    {
        return new TransactionMessageHandler(
            $this->createMock(LoggerInterface::class),
            Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->addDefaultDoctrineAnnotationReader()
                ->getValidator(),
            $this->createMock(DiscordNotifier::class),
            $this->createMock(SerializerInterface::class),
            $this->getContainer()->get(TransactionBuilder::class),
            $this->getContainer()->get(TransactionHandler::class),
        );
    }
}
