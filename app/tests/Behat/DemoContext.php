<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class DemoContext extends KernelTestCase implements Context
{
    /** @var Response|null */
    private $response;

    /**
     * Ensure to fully reset the test database fixtures features, allowing easy knowledge of the current
     * database state at the beginning of each features
     *
     * @Given I reload the fixtures
     *
     * @BeforeFeature
     */
    public static function prepareFixtures()
    {
        system(sprintf('bin/console hautelook:fixtures:load -n --env="test"'));
    }

    /**
     * Clean cache before feature
     *
     * @BeforeFeature @needCleanCachePool
     */
    public static function clearCachePool(): void
    {
        system(sprintf('bin/console cache:pool:clear cache.app --env="test"'));
    }

    /**
     * Run tearDown method from KernalTestCase to clean up the test kernel
     *
     * @AfterScenario
     */
    public function afterScenario(): void
    {
        $this->tearDown();
    }

    /**
     * @When a demo scenario sends a request to :path
     */
    public function aDemoScenarioSendsARequestTo(string $path): void
    {
        $this->response = self::getKernelClass()->handle(Request::create($path, 'GET'));
    }

    /**
     * @Then the response should be received
     */
    public function theResponseShouldBeReceived(): void
    {
        if (null === $this->response) {
            throw new \RuntimeException('No response received');
        }
    }
}
