<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Trait BaseContextTrait
 *
 * A trait that allows a utilising context to access the ui and mink functionality loaded in the BaseContext
 */
trait BaseContextTrait
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected MinkContext $ui;

    /** @var bool[] */
    protected array $featureFlags;

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /** @psalm-var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();

        $base               = $environment->getContext(BaseContext::class);
        $this->ui           = $base->ui; // MinkContext gathered in BaseContext
        $this->featureFlags = $base->featureFlags;
    }

    /**
     * Checks the response for a particular header being set with a specified value
     *
     * @throws ExpectationException
     */
    public function assertResponseHeader(string $name, string $value): void
    {
        $this->ui->assertSession()->responseHeaderEquals($name, $value);
    }

    /**
     * Checks for an exact url match (including scheme, domain, path, fragments and queries)
     *
     * @throws ExpectationException
     * @throws DriverException
     * @throws UnsupportedDriverActionException
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

        $json = json_decode((string) $body, true);

        if ($json === null) {
            throw new ExpectationException(
                'Expected valid JSON but did not find it',
                $this->ui->getSession()->getDriver()
            );
        }

        return $json;
    }

    /**
     * Asserts that the current url was accessed over a https connection
     *
     * @throws DriverException
     * @throws ExpectationException
     * @throws UnsupportedDriverActionException
     */
    public function assertHttps(): void
    {
        $actual = $this->ui->getSession()->getDriver()->getCurrentUrl();

        $scheme = parse_url((string) $actual, PHP_URL_SCHEME);

        if ($scheme !== 'https') {
            throw new ExpectationException(
                sprintf('Current scheme is "%s", but "https" expected.', $scheme),
                $this->ui->getSession()->getDriver()
            );
        }
    }
}
