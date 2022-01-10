<?php

namespace Service\Messenger\Handler;

use App\Entity\Wallet;
use App\Message\TransactionMessage;
use App\Service\Messenger\Handler\TransactionMessageHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandlerTest extends TestCase
{

    public function setUp():void
    {

    }

    /** @dataProvider getMessages */
    public function testTransactionMessageValidation($transactionMessage)
    {
        $transactionMessageHandler = $this->getTransactionMessageHandler();
        $transactionMessageHandler($transactionMessage);

        $this->expectException(ConstraintDefinitionException::class);

        dd($this->getExpectedException());
    }

    public function getMessages(): array
    {
        return
            [
                [new TransactionMessage()],
                [new TransactionMessage(amount: '-300')],
                [new TransactionMessage(walletFrom: new Wallet())],
                [new TransactionMessage(walletTo: new Wallet())],
                [new TransactionMessage(type: 'wrong_type')],
            ];
    }

    private function getTransactionMessageHandler(): TransactionMessageHandler
    {
        return new TransactionMessageHandler(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ValidatorInterface::class),
            $this->createMock(DiscordNotifier::class),
            $this->createMock(SerializerInterface::class)
        );
    }
}
