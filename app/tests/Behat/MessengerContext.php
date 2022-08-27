<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Service\Messenger\Serializer\TransactionMessageSerializer;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerContext extends KernelTestCase implements Context
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TransactionMessageSerializer $transactionMessageSerializer,
    ) {
    }

    /**
     * @BeforeFeature @messenger
     */
    public static function behatBeforeMessengerFeature()
    {
        system(sprintf('supervisorctl stop messenger-consume:*'));
    }

    /**
     * @AfterFeature @messenger
     */
    public static function behatAfterMessengerFeature()
    {
        system(sprintf('supervisorctl start messenger-consume:*'));
    }

    /**
     * @When /^I send a TransactionMessage to the queue with body:$/
     */
    public function iSendATransactionMessageToTheQueueWithBody(PyStringNode $string)
    {
        $envelope = $this->decodeString($string);
        $this->messageBus->dispatch($envelope);
    }

    /**
     * @Then /^I run the messenger consumer command and consume "([^"]*)" messages$/
     */
    public function iStartTheMessengerConsumerAndConsumeMessages(int $limit)
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'receivers' => ['async_transaction'],
                '--limit' => $limit,
                '--env' => 'test',
            ],
        );
    }

    private function decodeString(PyStringNode $string): Envelope
    {
        return $this->transactionMessageSerializer->decode(['body' => $string->getRaw()]);
    }
}
