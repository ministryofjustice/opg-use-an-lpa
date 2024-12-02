<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use BehatTest\Context\UI\BaseUiContext;
use BehatTest\Context\UI\SharedState;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Trait BaseUiContextTrait
 *
 * A trait that allows a utilising context to access the ui and mink functionality loaded in the BaseUiContext
 */
trait BaseUiContextTrait
{
    protected BaseUiContext $base;
    protected MinkContext $ui;
    protected MockHandler $apiFixtures;
    protected AwsMockHandler $awsFixtures;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();

        $this->base        = $environment->getContext(BaseUiContext::class);
        $this->ui          = $this->base->ui; // MinkContext gathered in BaseUiContext
        $this->apiFixtures = $this->base->apiFixtures;
        $this->awsFixtures = $this->base->awsFixtures;
    }

    /**
     * @AfterScenario
     */
    public function outputLogsOnFailure(AfterScenarioScope $scope): void
    {
        $logger = $this->base->container->get(LoggerInterface::class);

        if ($logger instanceof Logger) {
            /** @var TestHandler $testHandler */
            $testHandler = array_filter(
                $logger->getHandlers(),
                fn ($handler) => $handler instanceof TestHandler
            )[0];

            if (!$scope->getTestResult()->isPassed()) {
                foreach ($testHandler->getRecords() as $record) {
                    print_r($record['formatted']);
                }
            }

            $logger->reset();
        }
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
     * Verifies a Javascript accordion element is open
     *
     * @param string $searchStr
     * @return bool
     */
    public function elementIsOpen(string $searchStr): bool
    {
        $page        = $this->ui->getSession()->getPage();
        $element     = $page->find('css', $searchStr);
        $elementHtml = $element->getOuterHtml();
        return str_contains($elementHtml, ' open');
    }

    public function sharedState(): SharedState
    {
        return SharedState::getInstance();
    }
}
