<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Notifier\DiscordNotifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ValidatorInterface $validator,
        private DiscordNotifier $discordNotifier,
        private SerializerInterface $serializer,
        private TransactionBuilder $transactionBuilder,
        private TransactionHandler $transactionHandler,
    ) {
    }

    public function __invoke(TransactionMessage $transactionMessage)
    {
        try {
            $this->validate($transactionMessage);
            $transaction = $this->transactionBuilder->build($transactionMessage);
            $this->transactionHandler->handleTransaction($transaction);
        } catch (ConstraintDefinitionException $exception) {
            $this->handleException($exception, $transactionMessage);
        }
    }

    /**
     * @throws ConstraintDefinitionException
     */
    private function validate(TransactionMessage $transactionMessage): void
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($transactionMessage);

        if (\count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new ConstraintDefinitionException($errorsString);
        }
    }

    private function handleException(ConstraintDefinitionException $exception, TransactionMessage $transactionMessage)
    {
        $jsonMessage = $this->serializer->serialize(
            [
                'amount' => $transactionMessage->getAmount(),
                'type' => $transactionMessage->getType(),
                'message' => $transactionMessage->getMessage(),
                'walletFrom' => $transactionMessage->getWalletFrom()?->getId(),
                'walletTo' => $transactionMessage->getWalletTo()?->getId(),
            ],
            'json',
        );

        // TODO dispatch an event too for the discord notifier and maybe the error log
        $this->discordNotifier->notifyErrorOnTransaction($exception->getMessage(), $jsonMessage);
        $this->logger->critical($exception->getMessage(), [$exception->getMessage(), $jsonMessage]);

        throw $exception;
    }
}
