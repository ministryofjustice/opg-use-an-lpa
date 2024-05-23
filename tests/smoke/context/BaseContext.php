<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterStep;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use DMore\ChromeDriver\ChromeDriver;

class BaseContext implements Context
{
    use FeatureFlagContextTrait;

    /** @var string The domain/url of the service under test */
    public string $baseUrl = 'http://localhost';

    public string $oldBaseUrl = 'http://localhost';

    /**
     * @var MinkContext An accessible mink instance that drives UI interactions
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public MinkContext $ui;

    #[BeforeScenario]
    public function setupBaseUrl(BeforeScenarioScope $scope): void
    {
        $this->baseUrl = match ($scope->getSuite()->getName()) {
            'viewer' => getenv('BEHAT_VIEWER_URL') ?: 'http://viewer-web',
            'actor' => getenv('BEHAT_ACTOR_URL') ?: 'http://actor-web',
            default => throw new SuiteConfigurationException(
                sprintf('Suite "%s" does not have a valid url configured', $scope->getSuite()->getName()),
                $scope->getSuite()->getName(),
            ),
        };
        /** @psalm-var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();

        // we need to set this on *all* contexts
        foreach ($environment->getContexts() as $context) {
            if ($context instanceof RawMinkContext) {
                $context->setMinkParameter('base_url', $this->baseUrl);
            }
        }
    }

    #[BeforeScenario]
    public function setupOldBaseUrl(BeforeScenarioScope $scope): void
    {
        $this->oldBaseUrl = match ($scope->getSuite()->getName()) {
            'viewer' => getenv('BEHAT_OLD_VIEWER_URL') ?: 'http://viewer-web',
            'actor' => getenv('BEHAT_OLD_ACTOR_URL') ?: 'http://actor-web',
            default => throw new SuiteConfigurationException(
                sprintf('Suite "%s" does not have a valid url configured', $scope->getSuite()->getName()),
                $scope->getSuite()->getName(),
            ),
        };

        /** @psalm-var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();

        // we need to set this on *all* contexts
        foreach ($environment->getContexts() as $context) {
            if ($context instanceof RawMinkContext) {
                $context->setMinkParameter('old_base_url', $this->oldBaseUrl);
            }
        }
    }

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /** @psalm-var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->ui    = $environment->getContext(MinkContext::class);
    }

    /**
     * Take screenshot when step fails.
     */
    #[AfterStep]
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
