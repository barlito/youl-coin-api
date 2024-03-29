<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Behat\Assert\PropertyAssertTrait;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class InMemoryContext extends KernelTestCase implements Context
{
    use PropertyAssertTrait;

    public function __construct()
    {
        parent::__construct('InMemory Behat Context');
    }

    /**
     * @Given /^"([^"]*)" message(?:s|) has been sent on "([^"]*)" transport$/
     *
     * @throws \Exception
     */
    public function messageHasBeenSentOnTransport($number, $transportName)
    {
        $transport = $this->getTransport($transportName);

        $this->assertSame(\count($transport->getSent()), (int) $number, 'Number of sent messages is not correct');
    }

    /**
     * @Given /^the "([^"]*)" message sent on "([^"]*)" transport should match:$/
     *
     * @throws \JsonException
     */
    public function theMessageSentOnTransportShouldMatch(int $messageNumber, $transportName, PyStringNode $string)
    {
        $transport = $this->getTransport($transportName);
        $message = $transport->getSent()[$messageNumber - 1]->getMessage();

        $data = json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data as $path => $expected) {
            $this->assertRow($path, $expected, $message);
        }
    }

    private function getTransport($transportName): InMemoryTransport
    {
        return self::getContainer()->get('test.service_container')->get('messenger.transport.' . $transportName);
    }
}
