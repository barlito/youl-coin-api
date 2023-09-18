<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Behat\Mock\TransactionNotifierMock;
use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotifierContext extends KernelTestCase implements Context
{
    public function __construct(
        private readonly TransactionNotifierMock $notifier,
        string $name = null,
        array $data = [],
        $dataName = '',
    ) {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @Then the Discord notifier should have notified :number notifications
     */
    public function theNotifierShouldHaveNotifiedNotifications($number)
    {
        $this->assertEquals($number, $this->notifier->countNotifications());
    }

    /**
     * @Then the Discord notifier should have notified :number error
     */
    public function theNotifierShouldHaveNotifiedError($number)
    {
        $this->assertEquals($number, $this->notifier->countErrorNotifications());
    }

    /**
     * @BeforeScenario
     */
    public function flushNotifier(): void
    {
        $this->notifier->reset();
    }
}
