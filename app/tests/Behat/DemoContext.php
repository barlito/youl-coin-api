<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class DemoContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var Response|null */
    private $response;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeSuite
     */
    public static function behatBeforeSuite()
    {
        system(sprintf('supervisorctl stop messenger-consume:*'));
    }

    /**
     * Ensure to fully reset the test database fixtures features, allowing easy knowledge of the current
     * database state at the beginning of each features
     *
     * @Given I reload the fixtures
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
     * @When a demo scenario sends a request to :path
     */
    public function aDemoScenarioSendsARequestTo(string $path): void
    {
        $this->response = $this->kernel->handle(Request::create($path, 'GET'));
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
