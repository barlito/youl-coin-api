<?php

declare(strict_types=1);

namespace App\Service\Handler\Abstraction;

use App\Message\TransactionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ConstraintDefinitionException
     */
    protected function validate(TransactionMessage $transactionMessage): void
    {
        $errors = $this->validator->validate($transactionMessage);

        if (\count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new ConstraintDefinitionException($errorsString);
        }
    }

    protected function persistOneEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
