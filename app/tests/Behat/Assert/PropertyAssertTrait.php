<?php

declare(strict_types=1);

namespace App\Tests\Behat\Assert;

use Symfony\Component\PropertyAccess\PropertyAccess;

trait PropertyAssertTrait
{
    /**
     * @throws \JsonException
     */
    public function assertRow(string $path, mixed $expected, mixed $entity): void
    {
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
}
