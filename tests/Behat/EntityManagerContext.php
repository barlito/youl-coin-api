<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

// N'étend plus TestCase : TestCase::__construct est final depuis PHPUnit 10,
// incompatible avec l'injection par constructeur des contexts Behat.
final class EntityManagerContext implements Context
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private string $entityNamespace,
    ) {
    }

    /**
     * @Given a :entityClass entity found by :findByQueryString should match:
     */
    public function aEntityFoundByShouldMatch(string $entityClass, string $findByQueryString, TableNode $table)
    {
        $findBy = $this->parseFindByQueryString($findByQueryString);
        $this->entityManager->clear();
        $entity = $this->getRepository($entityClass)->findOneBy($findBy);
        $this->valueShouldMatch($entity, $table);
    }

    /**
     * @Given a :entityClass entity found by :findByQueryString should not exist
     */
    public function aEntityFoundByShouldNotBeFound(string $entityClass, string $findByQueryString)
    {
        $findBy = $this->parseFindByQueryString($findByQueryString);
        $this->entityManager->clear();
        $entity = $this->getRepository($entityClass)->findOneBy($findBy);
        Assert::assertSame($entity, null, 'Entity found.');
    }

    /**
     * @Given a :entityClass entity found by :findByQueryString should be deleted
     */
    public function aEntityFoundByShouldBeDeleted(string $entityClass, string $findByQueryString)
    {
        $findBy = $this->parseFindByQueryString($findByQueryString);
        $this->entityManager->clear();
        $entity = $this->getRepository($entityClass)->findOneBy($findBy);
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    private function valueShouldMatch(object $entity, TableNode $table)
    {
        foreach ($table->getRowsHash() as $path => $expected) {
            $this->assertRow($path, $expected, $entity);
        }
    }

    private function assertRow(string $path, mixed $expected, mixed $entity)
    {
        $expected = $this->parseExpected($expected);

        $actualValue = $this->getValueAtPath($entity, $path, false);

        Assert::assertEquals($expected, $actualValue, \sprintf(
            "The element '%s' value '%s' is not equal to expected '%s'",
            $path,
            $this->getAsString($actualValue),
            $this->getAsString($expected),
        ));
    }

    private function parseFindByQueryString(string $findByQueryString): array
    {
        parse_str($findByQueryString, $findBy);

        foreach ($findBy as $key => $value) {
            $type = null;
            if (str_contains($key, ':')) {
                $parts = explode(':', $key);
                if (2 !== \count($parts)) {
                    throw new \RuntimeException(
                        \sprintf(
                            'Invalid type identifier given to look for an entity "%s"',
                            $key,
                        ),
                    );
                }

                unset($findBy[$key]);

                $key = $parts[0];
                $type = $parts[1];
            }

            $findBy[$key] = $this->handleQueryStringTypeHinting($value, $type);
        }

        return $findBy;
    }

    private function handleQueryStringTypeHinting(mixed $value, ?string $type = null): mixed
    {
        if ('null' === $value) {
            return null;
        }

        return match ($type) {
            'date' => new \DateTime($value),
            default => $value,
        };
    }

    private function getRepository(string $entityClass): ObjectRepository
    {
        return $this->entityManager->getRepository($this->entityNamespace . '\\' . $entityClass);
    }

    private function getAsString($input): string
    {
        if ($input instanceof \DateTimeInterface) {
            return $input->format(DATE_ATOM);
        }

        if ($input instanceof \UnitEnum) {
            return $input->value;
        }

        return \is_array($input) && false !== json_encode($input) ?
            json_encode($input) :
            (string) $input;
    }

    /**
     * @return mixed|null
     */
    private function getValueAtPath($entity, string $path, bool $allowMissingPath)
    {
        try {
            return PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor()
                ->getValue($entity, $path)
            ;
        } catch (AccessException | NoSuchIndexException $e) {
            if (!$allowMissingPath) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * @Then I create a ":entityClass" entity with data:
     */
    public function iCreateAEntityWithData($entityClass, PyStringNode $data)
    {
        $entity = $this->serializer->deserialize($data->getRaw(), $this->getRepository($entityClass)->getClassName(), 'json');

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * @Given I should find :count :entityClass entity found by :findByQueryString
     */
    public function iShouldFindEntityWithParams(int $number, string $entityClass, string $findByQueryString)
    {
        $findBy = $this->parseFindByQueryString($findByQueryString);
        $this->entityManager->clear();
        $entities = $this->getRepository($entityClass)->findBy($findBy);

        $this->assertCount($number, $entities, \sprintf('Found %d entities instead of %d', \count($entities), $number));
    }

    private function parseExpected(mixed $expected): mixed
    {
        if (\is_string($expected) && str_starts_with($expected, '!php/enum')) {
            $enum = substr($expected, 10);
            if ($useValue = str_ends_with($enum, '->value')) {
                $enum = substr($enum, 0, -7);
            }
            if (!\defined($enum)) {
                throw new ParseException(\sprintf('The enum "%s" is not defined.', $enum));
            }

            $value = \constant($enum);

            if (!$value instanceof \UnitEnum) {
                throw new ParseException(\sprintf('The string "%s" is not the name of a valid enum.', $enum));
            }
            if (!$useValue) {
                return $value;
            }
            if (!$value instanceof \BackedEnum) {
                throw new ParseException(\sprintf('The enum "%s" defines no value next to its name.', $enum));
            }

            return $value->value;
        }

        return $expected;
    }
}
