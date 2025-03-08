<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Gherkin\Node\PyStringNode;

final class FormContext extends AuthContext
{
    /**
     * @When (I )submit the form with :button on button to :url with body:
     */
    public function iSubmitTheFormToUrlWithBody(string $button, string $url, PyStringNode $string): void
    {
        $payload = json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);

        $client = self::getClient();
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
