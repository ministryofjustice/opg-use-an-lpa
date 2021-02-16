<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use BehatTest\Context\UI\BaseUiContext;
use JSHayes\FakeRequests\MockHandler;
use JSHayes\FakeRequests\RequestHandler;

/**
 * Trait BaseUiContextTrait
 *
 * A trait that allows a utilising context to access the ui and mink functionality loaded in the BaseUiContext
 *
 * @package BehatTest\Context
 */
trait BaseUiContextTrait
{
    /**
     * @var BaseUiContext
     */
    protected $base;

    /**
     * @var MinkContext
     */
    protected $ui;

    /**
     * @var MockHandler
     */
    protected $apiFixtures;

    /**
     * @var AwsMockHandler
     */
    protected $awsFixtures;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->base = $environment->getContext(BaseUiContext::class);
        $this->ui = $this->base->ui; // MinkContext gathered in BaseUiContext
        $this->apiFixtures = $this->base->apiFixtures;
        $this->awsFixtures = $this->base->awsFixtures;
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

    /**
     * Allows context steps to optionally store an api request mock as returned from calls to
     * `$this->apiFixtures->get|patch|post()`
     *
     * @param RequestHandler $request
     */
    public function setLastRequest(RequestHandler $request): void
    {
        $this->base->lastApiRequest = $request;
    }

    /**
     * Allow context steps to optionally fetch the last api request that was stored via a previous
     * call to {@link setLastRequest()}
     *
     * This function may not return the request you're expecting so ensure your feature test steps
     * set the value you want before use.
     *
     * @return RequestHandler
     */
    public function getLastRequest(): RequestHandler
    {
        return $this->base->lastApiRequest;
    }

    /**
     * Verifies a Javascript accordion element is open
     *
     * @param string $searchStr
     *
     * @return bool
     */
    public function elementisOpen(string $searchStr)
    {
        $page = $this->ui->getSession()->getPage();
        $element = $page->find('css', $searchStr);
        $elementHtml = $element->getOuterHtml();
        return str_contains($elementHtml, ' open');
    }
}
