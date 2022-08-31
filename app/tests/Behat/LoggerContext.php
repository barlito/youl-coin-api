<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Behat\Mock\LoggerMock;
use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LoggerContext extends KernelTestCase implements Context
{
    public function __construct(
        private readonly LoggerMock $logger,
        ?string $name = null,
        array $data = [],
        $dataName = '',
    ) {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @Then /^the logger logged the error with message "([^"]*)"$/
     */
    public function theLoggerLoggedTheErrorWithMessage(string $message): void
    {
        $this->assertTrue(
            null !== $this->logger->getLoggedMessage($message),
            "Error with message '" . $message . "' is not logged by the logger",
        );
    }

    /**
     * @Then /^the logger logged an error containing "([^"]*)"$/
     */
    public function theLoggerLoggedAnErrorContaining(string $message): void
    {
        $this->assertTrue(
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
