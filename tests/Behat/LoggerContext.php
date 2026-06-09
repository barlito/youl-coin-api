<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Behat\Mock\LoggerMock;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

// N'étend plus KernelTestCase : TestCase::__construct est final depuis PHPUnit 10,
// incompatible avec l'injection par constructeur des contexts Behat.
class LoggerContext implements Context
{
    public function __construct(
        private readonly LoggerMock $logger,
    ) {
    }

    /**
     * @Then the logger logged the error with message :message
     */
    public function theLoggerLoggedTheErrorWithMessage(string $message): void
    {
        Assert::assertTrue(
            null !== $this->logger->getLoggedMessage($message),
            "Error with message '" . $message . "' is not logged by the logger",
        );
    }

    /**
     * @Then the logger logged an error containing :message
     */
    public function theLoggerLoggedAnErrorContaining(string $message): void
    {
        Assert::assertTrue(
            null !== $this->logger->containsLoggedMessage($message),
            "Error with message '" . $message . "' is not logged by the logger",
        );
    }

    /**
     * @BeforeScenario
     */
    public function flushLogger(): void
    {
        $this->logger->reset();
    }
}
