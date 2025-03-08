<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Transaction;
use App\Service\Handler\TransactionHandler;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

readonly class TransactionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private TransactionHandler $transactionHandler,
    ) {
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Transaction
    {
        if (!$data instanceof Transaction) {
            throw new UnexpectedTypeException($data, Transaction::class);
        }

        return $this->transactionHandler->handleTransaction($data);
    }
}
