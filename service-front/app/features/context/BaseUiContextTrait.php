<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use BehatTest\Context\UI\BaseUiContext;
use BehatTest\Context\UI\SharedState;
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

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();

        $this->base        = $environment->getContext(BaseUiContext::class);
        $this->ui          = $this->base->ui; // MinkContext gathered in BaseUiContext
        $this->apiFixtures = $this->base->apiFixtures;
        $this->awsFixtures = $this->base->awsFixtures;
    }

    #[AfterScenario]
    public function outputLogsOnFailure(AfterScenarioScope $scope): void
    {
        $logger = $this->base->container->get(LoggerInterface::class);

        if ($logger instanceof Logger) {
            /** @var TestHandler $testHandler */
            $testHandler = array_filter(
                $logger->getHandlers(),
                fn ($handler): bool => $handler instanceof TestHandler
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
     * @throws ExpectationException
     */
    public function assertResponseHeader($name, $value): void
    {
        $this->ui->assertSession()->responseHeaderEquals($name, $value);
    }

    /**
     * Checks the response body for a piece of text subject to translation.
     *
     * This is only able to find translations for text where the text given is equal to the full translation key.
     * Since we use English language as our keys this means you will be unable to use this if you are asserting on
     * partial text content.
     *
     * For example, if the text in a template is "Your LPA details" you will not be able to use this function to assert
     * the page contains "Your LPA".
     *
     * Additionally, if you give it text that is not a translation key this will silently succeed as the default
     * behaviour of gettext it to return the key if no translation is found.
     *
     * @param string $text The full translation key of a piece of text
     * @param array  $replacements An array of replacement tokens to be substituted into the translation
     * @param int    $count The number items to pluralise the translation with
     * @return void
     * @throws ExpectationException
     */
    public function assertPageContainsTranslatedText(
        string $text,
        array $replacements = [],
        ?int $count = null,
    ): void {
        $tt = $this->base->translator->translate($text, $replacements, count: $count);

        $this->ui->assertSession()->pageTextContains($tt);
    }

    /**
     * The inverse of {@see assertPageContainsTranslatedText}.
     *
     * With all the same conditions and downsides.
     *
     * @param string $text The full translation key of a piece of text
     * @param array $replacements An array of replacement tokens to be substituted into the translation
     * @param int $count The number items to pluralise the translation with
     * @return void
     * @throws ExpectationException
 */
    public function assertPageNotContainsTranslatedText(
        string $text,
        array $replacements = [],
        ?int $count = null,
    ): void {
        $tt = $this->base->translator->translate($text, $replacements, count: $count);

        $this->ui->assertSession()->pageTextNotContains($tt);
    }

    /**
     * Verifies a Javascript accordion element is open
     */
    public function elementIsOpen(string $searchStr): bool
    {
        $page        = $this->ui->getSession()->getPage();
        $element     = $page->find('css', $searchStr);
        $elementHtml = $element->getOuterHtml();
        return str_contains((string) $elementHtml, ' open');
    }

    public function sharedState(): SharedState
    {
        return SharedState::getInstance();
    }
}
