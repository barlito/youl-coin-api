<?php

declare(strict_types=1);

namespace App\Service\Messenger\Handler;

use App\Message\TransactionMessage;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\Abstraction\AbstractHandler;
use App\Service\Handler\TransactionHandler;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandler extends AbstractHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DiscordNotifier $discordNotifier,
        private readonly SerializerInterface $serializer,
        private readonly TransactionBuilder $transactionBuilder,
        private readonly TransactionHandler $transactionHandler,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($entityManager, $validator);
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

    private function handleException(ConstraintDefinitionException $exception, TransactionMessage $transactionMessage)
    {
        //todo create a class on barlito/utils and move this
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
