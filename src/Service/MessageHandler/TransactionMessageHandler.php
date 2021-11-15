<?php

declare(strict_types=1);

namespace App\Service\MessageHandler;

use App\DTO\TransactionMessageDTO;
use App\Entity\Transaction;
use App\Message\TransactionMessage;
use App\Service\Builder\TransactionMessageDtoBuilder;
use App\Service\Notifier\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionMessageDtoBuilder $transactionMessageDtoBuilder,
        private LoggerInterface $logger,
        private ValidatorInterface $validator,
        private DiscordNotifier $discordNotifier,
    ) {
    }

    public function __invoke(TransactionMessage $message)
    {
        try {
            $transactionMessageDTO = $this->transactionMessageDtoBuilder->build($message);
            $this->validate($transactionMessageDTO);
            $this->processTransaction($transactionMessageDTO);
            $this->entityManager->flush();
        } catch (UnexpectedValueException | ConstraintDefinitionException $e) {
            $this->discordNotifier->notifyErrorOnTransaction($e->getMessage(), $message->getContent());
            $this->logger->critical($e->getMessage(), [$e->getMessage(), $message->getContent()]);

            return;
        }
    }

    /**
     * @throws ConstraintDefinitionException
     */
    private function validate(TransactionMessageDTO $transactionMessageDTO)
    {
        /** @var ConstraintViolation[] $errors */
        $errors = $this->validator->validate($transactionMessageDTO);

        if (\count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new ConstraintDefinitionException($errorsString);
        }
    }

    private function processTransaction(TransactionMessageDTO $transactionMessageDTO)
    {
        $walletFrom = $transactionMessageDTO->getWalletFrom();
        $walletTo = $transactionMessageDTO->getWalletTo();

        $walletFrom->setAmount(bcsub($walletFrom->getAmount(), $transactionMessageDTO->getAmount()));
        $walletTo->setAmount(bcadd($walletTo->getAmount(), $transactionMessageDTO->getAmount()));

        $transaction = new Transaction();
        $transaction
            ->setAmount($transactionMessageDTO->getAmount())
            ->setWalletFrom($walletFrom)
            ->setWalletTo($walletTo)
            ->setType($transactionMessageDTO->getType())
            ->setMessage($transactionMessageDTO->getMessage())
        ;

        $this->entityManager->persist($transaction);

        $this->discordNotifier->notifyNewTransaction($transaction);
    }
}
