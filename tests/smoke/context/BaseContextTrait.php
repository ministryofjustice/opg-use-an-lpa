<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Trait BaseContextTrait
 *
 * A trait that allows a utilising context to access the ui and mink functionality loaded in the BaseContext
 *
 * @package Test\Context
 */
trait BaseContextTrait
{
    protected MinkContext $ui;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();

        $base = $environment->getContext(BaseContext::class);
        $this->ui = $base->ui; // MinkContext gathered in BaseContext
    }

    /**
     * Checks the response for a particular header being set with a specified value
     *
     * @param $name
     * @param $value
     * @throws ExpectationException
     */
    public function assertResponseHeader(string $name, string $value): void
    {
        $this->ui->assertSession()->responseHeaderEquals($name, $value);
    }

    /**
     * Checks for an exact url match (including scheme, domain, path, fragments and queries)
     *
     * @param string $expected
     * @param string $actual
     * @throws ExpectationException
     */
    public function assertExactUrl(string $expected, string $actual)
    {
        if ($expected !== $actual) {
            throw new ExpectationException(
                sprintf('Current page is "%s", but "%s" expected.', $actual, $expected),
                $this->ui->getSession()->getDriver()
            );
        }
    }
}