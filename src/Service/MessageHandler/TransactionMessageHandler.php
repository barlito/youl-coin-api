<?php

declare(strict_types=1);

namespace App\Service\MessageHandler;

use App\DTO\TransactionMessageDTO;
use App\Entity\Transaction;
use App\Message\TransactionMessage;
use App\Money\YoulCoinCurrency;
use App\Money\YoulCoinFormatter;
use App\Repository\WalletRepository;
use App\Service\Builder\TransactionMessageDtoBuilder;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Money;
use NumberFormatter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface       $entityManager,
        private TransactionMessageDtoBuilder $transactionMessageDtoBuilder,
        private LoggerInterface              $logger,
        private ValidatorInterface           $validator
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
            //todo send a webhook
            $this->logger->critical($e->getMessage(), [$message->getContent()]);
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

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
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
            ->setWalletTo($walletTo);

        $this->entityManager->persist($transaction);
    }
}
