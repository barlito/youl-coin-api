<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\TransactionMessage;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private ValidatorInterface     $validator,
        private DiscordNotifier        $discordNotifier,
        private SerializerInterface    $serializer
    ) {
    }

    public function __invoke(TransactionMessage $transactionMessage)
    {
        try {
            $this->validate($transactionMessage);
            $this->processTransaction($transactionMessage);
            $this->entityManager->flush();
        } catch (UnexpectedValueException|ConstraintDefinitionException $e) {
            $jsonMessage = $this->serializer->serialize(
                [
                    'amount'     => $transactionMessage->getAmount(),
                    'type'       => $transactionMessage->getType(),
                    'message'    => $transactionMessage->getMessage(),
                    'walletFrom' => $transactionMessage->getWalletFrom() ? $transactionMessage->getWalletFrom()->getId() : null,
                    'walletTo' => $transactionMessage->getWalletTo() ? $transactionMessage->getWalletTo()->getId() : null,
                ],
                'json'
            );

            //TODO dispatch an event too for the discord notifier and maybe the error log
            $this->discordNotifier->notifyErrorOnTransaction($e->getMessage(), $jsonMessage);
            $this->logger->critical($e->getMessage(), [$e->getMessage(), $jsonMessage]);

            return;
        }
    }

    /**
     * @throws ConstraintDefinitionException
     */
    private function validate(TransactionMessage $transactionMessage)
    {
        /** @var ConstraintViolation[] $errors */
        $errors = $this->validator->validate($transactionMessage);

        if (\count($errors) > 0) {
            $errorsString = (string)$errors;
            throw new ConstraintDefinitionException($errorsString);
        }
    }

    private function processTransaction(TransactionMessage $transactionMessage)
    {
        $walletFrom = $transactionMessage->getWalletFrom();
        $walletTo   = $transactionMessage->getWalletTo();

        $walletFrom->setAmount(bcsub($walletFrom->getAmount(), $transactionMessage->getAmount()));
        $walletTo->setAmount(bcadd($walletTo->getAmount(), $transactionMessage->getAmount()));

        $transaction = new Transaction();
        $transaction
            ->setAmount($transactionMessage->getAmount())
            ->setWalletFrom($walletFrom)
            ->setWalletTo($walletTo)
            ->setType($transactionMessage->getType())
            ->setMessage($transactionMessage->getMessage());

        $this->entityManager->persist($transaction);

        //TODO dispatch an event and handle the discord notif with a subscriber
        $this->discordNotifier->notifyNewTransaction($transaction);
    }
}
