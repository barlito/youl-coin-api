<?php

declare(strict_types=1);

namespace App\Tests\Behat\Assert;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Exception\ParseException;

trait PropertyAssertTrait
{
    /**
     * @throws \JsonException
     */
    public function assertRow(string $path, mixed $expected, mixed $entity): void
    {
        $expected = $this->parseExpected($expected);
        $assert = 'assertEquals';

        $actualValue = $this->getValueAtPath($entity, $path);

        $callable = [$this, $assert];
        if (!\is_callable($callable)) {
            return;
        }

        $callable($expected, $actualValue, sprintf(
            "The element '%s' value '%s' is not equal to expected '%s'",
            $path,
            $this->getAsString($actualValue),
            $this->getAsString($expected),
        ));
    }

    /**
     * @throws \JsonException
     */
    private function getAsString(mixed $input): string
    {
        if ($input instanceof \DateTimeInterface) {
            return $input->format(DATE_ATOM);
        }

        if ($input instanceof \UnitEnum) {
            return $input->value;
        }

        return \is_array($input) && false !== json_encode($input, JSON_THROW_ON_ERROR) ?
            json_encode($input, JSON_THROW_ON_ERROR) :
            (string) $input;
    }

    private function getValueAtPath(mixed $entity, string $path)
    {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor()
            ->getValue($entity, $path)
        ;
    }

    private function parseExpected(mixed $expected): mixed
    {
        if (\is_string($expected) && str_starts_with($expected, '!php/enum')) {
            $enum = substr($expected, 10);
            if ($useValue = str_ends_with($enum, '->value')) {
                $enum = substr($enum, 0, -7);
            }
            if (!\defined($enum)) {
                throw new ParseException(sprintf('The enum "%s" is not defined.', $enum));
            }

            $value = \constant($enum);

            if (!$value instanceof \UnitEnum) {
                throw new ParseException(sprintf('The string "%s" is not the name of a valid enum.', $enum));
            }
            if (!$useValue) {
                return $value;
            }
            if (!$value instanceof \BackedEnum) {
                throw new ParseException(sprintf('The enum "%s" defines no value next to its name.', $enum));
            }

            return $value->value;
        }

        return $expected;
    }
}
