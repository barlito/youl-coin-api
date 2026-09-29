<?php

declare(strict_types=1);

namespace App\Service\Handler\Abstraction;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractHandler
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationException
     */
    protected function validate(mixed $data, Constraint | array | null $constraints = null, string | GroupSequence | array $groups = []): void
    {
        $violations = $this->validator->validate($data, $constraints, $groups);

        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }

    protected function persistOneEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
