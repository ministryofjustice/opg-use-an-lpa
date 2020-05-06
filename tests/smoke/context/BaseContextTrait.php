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
     * @throws ExpectationException
     */
    public function assertExactUrl(string $expected): void
    {
        $actual = $this->ui->getSession()->getDriver()->getCurrentUrl();

        if ($expected !== $actual) {
            throw new ExpectationException(
                sprintf('Current page is "%s", but "%s" expected.', $actual, $expected),
                $this->ui->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that the response is JSON.
     *
     * @param string $mimeType An optional Mime type for the json.
     * @return array The parsed JSON response as an associative array.
     * @throws ExpectationException
     */
    public function assertJsonResponse(string $mimeType = 'application/json'): array
    {
        $this->assertResponseHeader('Content-Type', $mimeType);

        $body = $this->ui->getSession()->getPage()->getText();

        $json = json_decode($body, true);

        if ($json === null) {
            throw new ExpectationException(
                'Expected valid JSON but did not find it',
                $this->ui->getSession()->getDriver()
            );
        }

        return $json;
    }
}