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
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;

class TransactionMessageHandlerTest extends KernelTestCase
{
    use RecreateDatabaseTrait;

    private ?Wallet $walletFrom = null;
    private ?Wallet $walletTo = null;

    public function setUp(): void
    {
        $this->bootKernel();
    }

    /** @dataProvider getErrorMessages */
    public function testTransactionErrorMessageValidation(TransactionMessage $transactionMessage, string $exceptionMessage)
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('critical')
        ;

        $discordNotifierMock = $this->createMock(DiscordNotifier::class);
        $discordNotifierMock->expects($this->once())
            ->method('notifyErrorOnTransaction')
        ;

        $transactionMessageHandler = $this->getTransactionMessageHandler(logger: $loggerMock, discordNotifierMock: $discordNotifierMock);
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $transactionMessageHandler($transactionMessage);
    }

    private function getErrorMessages(): array
    {
        return
            [
                [
                    new TransactionMessage(null, $this->getWalletFrom(), $this->getWalletTo(), TransactionTypeEnum::CLASSIC),
                    'The amount value should not be blank.',
                ],
                [
                    new TransactionMessage('-300', $this->getWalletFrom(), $this->getWalletTo(), TransactionTypeEnum::CLASSIC),
                    'The amount value is not a positive integer',
                ],
                [
                    new TransactionMessage('', $this->getWalletFrom(), $this->getWalletTo(), TransactionTypeEnum::CLASSIC),
                    'The amount value should not be blank.',
                ],
                [
                    new TransactionMessage('9999999', $this->getWalletFrom(), $this->getWalletTo(), TransactionTypeEnum::CLASSIC),
                    'Not enough coins in from wallet.',
                ],
                [
                    new TransactionMessage('10', null, $this->getWalletTo(), TransactionTypeEnum::CLASSIC),
                    'The walletFrom value should not be null.',
                ],
                [
                    new TransactionMessage('10', $this->getWalletFrom(), null, TransactionTypeEnum::CLASSIC),
                    'The walletTo value should not be null.',
                ],
                [
                    new TransactionMessage('10', $this->getWalletFrom(), $this->getWalletFrom(), TransactionTypeEnum::CLASSIC),
                    'WalletFrom and WalletTo are the same.',
                ],
                [
                    new TransactionMessage('10', $this->getWalletFrom(), $this->getWalletTo(), null),
                    'The type value should not be null.',
                ],
                [
                    new TransactionMessage('10', $this->getWalletFrom(), $this->getWalletTo(), 'wrong'),
                    'The type value you selected is not a valid choice.',
                ],
            ];
    }

    private function getTransactionMessageHandler(
        LoggerInterface $logger = null,
        DiscordNotifier $discordNotifierMock = null,
    ): TransactionMessageHandler {
        return new TransactionMessageHandler(
            $logger ?? $this->createMock(LoggerInterface::class),
            $discordNotifierMock ?? $this->createMock(DiscordNotifier::class),
            $this->createMock(SerializerInterface::class),
            $this->getContainer()->get(TransactionBuilder::class),
            $this->getContainer()->get(TransactionHandler::class),
            $this->createMock(EntityManagerInterface::class),
            Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->addDefaultDoctrineAnnotationReader()
                ->getValidator(),
        );
    }

    private function getWalletFrom(): Wallet
    {
        return $this->walletFrom ?? $this->walletFrom = (new Wallet())
            ->setId('01FPD1DHMWPV4BHJQ82TSJEBJC')
            ->setAmount('9000')
            ->setType('user')
        ;
    }

    private function getWalletTo(): Wallet
    {
        return $this->walletTo ?? $this->walletTo = (new Wallet())
            ->setId('01FPD1DNKVFS5GGBPVXBT3YQ01')
            ->setAmount('8000')
            ->setType('user')
        ;
    }
}
