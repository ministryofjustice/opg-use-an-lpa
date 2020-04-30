<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
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
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function assertResponseHeader($name, $value): void
    {
        $this->ui->assertSession()->responseHeaderEquals($name, $value);
    }
}