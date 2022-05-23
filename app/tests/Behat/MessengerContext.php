<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionMessage;
use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerContext implements Context
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @When /^I send a TransactionMessage to the queue with WalletFrom ID:"([^"]*)" and WalletTo ID :"([^"]*)"$/
     */
    public function iSendATransactionMessageToTheQueueWithWalletFromIDAndWalletToID(string $walletFromId, string $walletToId)
    {
        $walletRepository = $this->entityManager->getRepository(Wallet::class);

        $this->messageBus->dispatch(
            new TransactionMessage(
                '10',
                $walletRepository->findOneBy(['id' => $walletFromId]),
                $walletRepository->findOneBy(['id' => $walletToId]),
                TransactionTypeEnum::CLASSIC,
            ),
        );
    }

    /**
     * @Then /^I start the messenger consumer$/
     */
    public function iStartTheMessengerConsumer()
    {
        system(sprintf('supervisorctl start messenger-consume-test:*'));
    }

    /**
     * @Given /^I stop the messenger consumer$/
     */
    public function iStopTheMessengerConsumer()
    {
        system(sprintf('supervisorctl stop messenger-consume-test:*'));
    }
}
