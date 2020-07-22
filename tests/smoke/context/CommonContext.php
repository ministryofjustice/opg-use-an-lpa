<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
use DMore\ChromeDriver\ChromeDriver;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Class CommonContext
 *
 * @package Test\Context
 *
 * @property array responseJson The json returned from a previous step
 */
class CommonContext implements Context
{
    use BaseContextTrait;

    /**
     * @Given I access the service homepage
     * @Given I access the viewer service
     * @Given I access the actor service
     */
    public function iAccessTheServiceHomepage(): void
    {
        $this->ui->visit('/home');
    }

    /**
     * @Given I access the service homepage insecurely
     * @Given I access the viewer service insecurely
     * @Given I access the actor service insecurely
     */
    public function iAccessTheViewerServiceInsecurely(): void
    {
        $baseUrlHost = parse_url($this->ui->getMinkParameter('base_url'), PHP_URL_HOST);
        $insecureUrl = sprintf('http://%s/home', $baseUrlHost);

        $this->ui->visit($insecureUrl);
    }

    /**
     * @Given I fetch the healthcheck endpoint
     */
    public function iFetchTheHealthcheckEndpoint(): void
    {
        $this->ui->visit('/healthcheck');
    }

    /**
     * @Then the service homepage should be shown securely
     * @Then the viewer service homepage should be shown securely
     * @Then the actor service homepage should be shown securely
     */
    public function theViewerServiceHomepageShouldBeShownSecurely(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $baseUrlHost = parse_url($this->ui->getMinkParameter('base_url'), PHP_URL_HOST);
        $expectedUrl = sprintf('https://%s/home', $baseUrlHost);

        $this->assertExactUrl($expectedUrl);
    }

    /**
     * @Then I see JSON output
     */
    public function iSeeJsonOutput(): void
    {
        $this->responseJson = $this->assertJsonResponse();
    }

    /**
     * @Then it contains a :key key\/value pair
     */
    public function itContainsAKeyValuePair(string $key): void
    {
        if (! array_key_exists($key, $this->responseJson)) {
            throw new ExpectationException(
                sprintf('Failed to find the key %s in the Json response', $key),
                $this->ui->getSession()
            );
        }
    }

    /**
     * @Then the session cookie is marked secure and httponly
     */
    public function theSessionCookiesIsMarkedSecureAndHttponly(): void
    {
        $this->ui->assertSession()->cookieExists('session');

        // could be moved to an assertion function in BaseContext but this is the *only* place this code will be used.
        /** @var ChromeDriver $driver */
        $driver = $this->ui->getSession()->getDriver();
        $cookies = $driver->getCookies();

        array_walk($cookies, function (array $cookie) {
            if ($cookie['name'] === 'session' && !$cookie['httpOnly']) {
                throw new ExpectationException(
                    sprintf('Unable to verify that the session cookie is "httpOnly"'),
                    $this->ui->getSession()
                );
            }

            if ($cookie['name'] === 'session' && !$cookie['secure']) {
                throw new ExpectationException(
                    sprintf('Unable to verify that the session cookie is "secure"'),
                    $this->ui->getSession()
                );
            }
        });
    }

    /**
     * @Then /^I receive headers that block external indexing$/
     */
    public function iReceiveHeadersThatBlockExternalIndexing()
    {
        $session = $this->ui->getSession();
        $xrobotstag = $session->getResponseHeader("X-Robots-Tag");
        assertNotNull($xrobotstag);
        assertContains('nofollow', $xrobotstag);
        assertContains('noindex', $xrobotstag);
    }
}
