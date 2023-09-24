<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Service\Messenger\Serializer\TransactionMessageSerializer;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final class MessengerContext extends KernelTestCase implements Context
{
    private readonly MessageBusInterface $messageBus;
    private readonly TransactionMessageSerializer $transactionMessageSerializer;

    public function __construct()
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
        $this->transactionMessageSerializer = self::getContainer()->get(TransactionMessageSerializer::class);
    }

    /**
     * @When I send and consume a TransactionMessage to the queue with body:
     */
    public function iSendATransactionMessageToTheQueueWithBody(PyStringNode $string)
    {
        // need to improve this step to be agnostic from the message type and fetch the right serializer
        // need to find the serializer linked to the type of message sent
        $envelope = $this->decodeString($string);

        try {
            $this->messageBus->dispatch($envelope->with(new ReceivedStamp('async_transaction'), new ConsumedByWorkerStamp()));
        } catch (\Throwable $e) {
            // do nothing on exception message thrown while dispatching
            // Exception message should be tested in the logger step
        }
    }

    private function decodeString(PyStringNode $string): Envelope
    {
        return $this->transactionMessageSerializer->decode(['body' => $string->getRaw()]);
    }
}
