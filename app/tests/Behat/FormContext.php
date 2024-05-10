<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FormContext extends WebTestCase implements Context
{
    /**
     * @When (I )submit the form with :button on button to :url with body:
     */
    public function iSubmitTheFormToUrlWithBody(string $button, string $url, PyStringNode $string)
    {
        $payload = json_decode($string->getRaw(), true);

        $client = FormContext::createClient();
        $client->followRedirects();

        $client->request('GET', $url);
        $client->submitForm($button, $payload);
    }

    /**
     * @Then (the )response status code should be :expected
     */
    public function assertResponseCode(int $expected): void
    {
        self::assertResponseStatusCodeSame($expected);
    }
}
