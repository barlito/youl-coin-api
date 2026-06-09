<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Behat\Mock\TransactionNotifierMock;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

// N'étend plus KernelTestCase : TestCase::__construct est final depuis PHPUnit 10,
// incompatible avec l'injection par constructeur des contexts Behat.
class NotifierContext implements Context
{
    public function __construct(
        private readonly TransactionNotifierMock $notifier,
    ) {
    }

    /**
     * @Then the Discord notifier should have notified :number notifications
     */
    public function theNotifierShouldHaveNotifiedNotifications($number)
    {
        Assert::assertEquals($number, $this->notifier->countNotifications());
    }

    /**
     * @Then the Discord notifier should have notified :number error
     */
    public function theNotifierShouldHaveNotifiedError($number)
    {
        Assert::assertEquals($number, $this->notifier->countErrorNotifications());
    }

    /**
     * @BeforeScenario
     */
    public function flushNotifier(): void
    {
        $this->notifier->reset();
    }
}
