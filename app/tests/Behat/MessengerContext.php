<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Entity\Wallet;
use App\Message\TransactionMessage;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerContext extends KernelTestCase implements Context
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @When /^I send a TransactionMessage to the queue with body:$/
     */
    public function iSendATransactionMessageToTheQueueWithBody(PyStringNode $string)
    {
        $payload = $this->decodeString($string);

        $walletRepository = $this->entityManager->getRepository(Wallet::class);

        $this->messageBus->dispatch(
            new TransactionMessage(
                (string) $payload['amount'],
                $walletRepository->findOneBy(['id' => $payload['walletFrom']]),
                $walletRepository->findOneBy(['id' => $payload['walletTo']]),
                (string) $payload['type'] ?? null,
                (string) $payload['message'] ?? null,
            ),
        );
    }

    /**
     * @Then /^I start the messenger consumer and consume "([^"]*)" messages$/
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

    /**
     * @Given /^I stop the messenger consumer$/
     */
    public function iStopTheMessengerConsumer()
    {
        system(sprintf('supervisorctl stop messenger-consume-test:*'));
    }

    /**
     * @throws Exception
     */
    private function decodeString(PyStringNode $string): array
    {
        try {
            return json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new Exception('Malformed JSON');
        }
    }
}
