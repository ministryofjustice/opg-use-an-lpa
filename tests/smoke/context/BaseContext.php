<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use DMore\ChromeDriver\ChromeDriver;

/**
 * Class BaseContext
 *
 * @package Test\Context
 */
class BaseContext implements Context
{
    /** @var string The domain/url of the service under test */
    public string $baseUrl = "http://localhost";

    /** @var MinkContext An accessible mink instance that drives UI interactions */
    public MinkContext $ui;

    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $scope
     */
    public function setupBaseUrl(BeforeScenarioScope $scope): void
    {
        switch ($scope->getSuite()->getName()) {
            case 'viewer':
                $this->baseUrl = getenv('BEHAT_VIEWER_URL') ?: 'http://viewer-web';
                break;
            case 'actor':
                $this->baseUrl = getenv('BEHAT_ACTOR_URL') ?: 'http://actor-web';
                break;
            default:
                throw new SuiteConfigurationException(
                    sprintf('Suite "%s" does not have a valid url configured', $scope->getSuite()->getName())
                );
        }

        $environment = $scope->getEnvironment();

        // we need to set this on *all* contexts
        foreach ($environment->getContexts() as $context) {
            if ($context instanceof RawMinkContext) {
                $context->setMinkParameter('base_url', $this->baseUrl);
            }
        }
    }
    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $scope
     */
    public function setupOldBaseUrl(BeforeScenarioScope $scope): void
    {
        switch ($scope->getSuite()->getName()) {
            case 'viewer':
                $this->OldBaseUrl = getenv('BEHAT_OLD_VIEWER_URL') ?: 'http://viewer-web';
                break;
            case 'actor':
                $this->OldBaseUrl = getenv('BEHAT_OLD_ACTOR_URL') ?: 'http://actor-web';
                break;
            default:
                throw new SuiteConfigurationException(
                    sprintf('Suite "%s" does not have a valid url configured', $scope->getSuite()->getName())
                );
        }

        $environment = $scope->getEnvironment();

        // we need to set this on *all* contexts
        foreach ($environment->getContexts() as $context) {
            if ($context instanceof RawMinkContext) {
                $context->setMinkParameter('old_base_url', $this->OldBaseUrl);
            }
        }
    }

    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->ui = $environment->getContext(MinkContext::class);
    }

    /**
     * Take screenshot when step fails.
     *
     * @AfterStep
     * @param AfterStepScope $scope
     */
    public function takeScreenshotAfterFailedStep(AfterStepScope $scope): void
    {
        if (! $scope->getTestResult()->isPassed()) {
            $driver = $this->ui->getSession()->getDriver();
            if (! ($driver instanceof ChromeDriver)) {
                return;
            }

            $filename = str_replace(' ', '_', $scope->getStep()->getText()) . '.png';
            $filename = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $filename);
            $this->ui->saveScreenshot($filename, realpath(__DIR__ . '/../failed_step_screenshots'));
        }
    }
}
