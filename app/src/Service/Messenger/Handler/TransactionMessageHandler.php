<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Message\TransactionMessage;
use App\Service\Handler\Abstraction\AbstractHandler;
use App\Service\Handler\TransactionHandler;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
class TransactionMessageHandler extends AbstractHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TransactionNotifierInterface $discordNotifier,
        private readonly SerializerInterface $serializer,
        private readonly TransactionHandler $transactionHandler,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($entityManager, $validator);
    }

    public function __invoke(TransactionMessage $transactionMessage): void
    {
        try {
            $this->validate($transactionMessage);
            $this->transactionHandler->handleTransactionMessage($transactionMessage);
        } catch (\Throwable $exception) {
            $this->handleException($exception, $transactionMessage);
        }
    }

    /**
     * @throws \Throwable
     */
    private function handleException(\Throwable $exception, TransactionMessage $transactionMessage): void
    {
        // todo create a class on barlito/utils and move this
        $serializerContext = (new ObjectNormalizerContextBuilder())
            ->withGroups(['default', 'test'])
            ->toArray()
        ;
        $jsonMessage = $this->serializer->serialize($transactionMessage, 'json', $serializerContext);

        $this->discordNotifier->notifyErrorOnTransaction($exception->getMessage(), $jsonMessage);
        $this->logger->critical($exception->getMessage(), [$exception->getMessage(), $jsonMessage]);

        throw $exception;
    }
}
