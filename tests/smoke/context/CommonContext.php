<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
use DMore\ChromeDriver\ChromeDriver;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;

/**
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
        $urlParts = parse_url($this->ui->getMinkParameter('base_url'));

        $insecureUrl = sprintf(
            'http://%s/home',
            $urlParts['host'] . (! empty($urlParts['port']) ? ':' . $urlParts['port'] : '')
        );

        $this->ui->visit($insecureUrl);
    }

    /**
     * @Given I access the service root path
     * @Given I access the viewer root path
     * @Given I access the actor root path
     */
    public function iAccessTheServiceRoot(): void
    {
        $urlParts = parse_url($this->ui->getMinkParameter('base_url'));

        $url = sprintf(
            '%s://%s/',
            $urlParts['scheme'],
            $urlParts['host'] . (! empty($urlParts['port']) ? ':' . $urlParts['port'] : '')
        );

        $this->ui->visit($url);
    }

    /**
     * @Given I access the service with the old web address
     */
    public function iAccessTheOldServiceUrl(): void
    {
        $urlParts = parse_url($this->ui->getMinkParameter('old_base_url'));

        $url = sprintf(
            '%s://%s/home',
            $urlParts['scheme'],
            $urlParts['host'] . (! empty($urlParts['port']) ? ':' . $urlParts['port'] : '')
        );

        $this->ui->visit($url);
    }

    /**
     * @Given I access the Welsh service homepage
     * @Given I access the Welsh viewer service
     * @Given I access the Welsh actor service
     */
    public function iAccessTheWelshServiceHomepage(): void
    {
        $this->ui->visit('/cy/home');
    }

    /**
     * @Then I can see English text
     */
    public function iCanSeeEnglishText(): void
    {
        $this->ui->assertPageContainsText('a lasting power of attorney');
    }

    /**
     * @Then /^I can see that the lpa has instructions and preferences images in summary$/
     */
    public function iCanSeeThatTheLpaHasInstructionsAndPreferencesImagesInSummary(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $instructionsElement = $this->ui->getMink()->getSession()->getPage()->findById('instructions_images');
        Assert::assertNotNull($instructionsElement);
        $preferencesElement = $this->ui->getMink()->getSession()->getPage()->findById('preferences_images');
        Assert::assertNotNull($preferencesElement);
    }

    /**
     * @Then /^I can see Welsh text$/
     */
    public function iCanSeeWelshText(): void
    {
        $this->ui->assertPageContainsText('atwrneiaeth arhosol');
    }

    /**
     * @Given I fetch the healthcheck endpoint
     */
    public function iFetchTheHealthcheckEndpoint(): void
    {
        $this->ui->visit('/healthcheck');
    }

    /**
     * @Then /^I receive headers that describe a content security policy$/
     */
    public function iReceiveHeadersThatDescribeAContentSecurityPolicy(): void
    {
        $session         = $this->ui->getSession();
        $cspHeader       = $session->getResponseHeader('Content-Security-Policy');
        $cspHeaderReport = $session->getResponseHeader('Content-Security-Policy-Report-Only');

        if ($cspHeader === null && $cspHeaderReport === null) {
            throw new ExpectationException(
                'CSP header or CSP report only header not in response',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }

        $header = $cspHeader ?? $cspHeaderReport;

        // default, highly restrictive, policy
        Assert::assertStringContainsString("default-src 'none';", $header);
    }

    /**
     * @Given /^the documents language is set to English$/
     */
    public function theDocumentsLanguageIsSetToEnglish(): void
    {
        $htmlElement = $this->ui->getMink()->getSession()->getPage()->find('css', 'html');
        if ($htmlElement === null) {
            throw new ExpectationException(
                'Html tag not found',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }

        if ($htmlElement->getAttribute('lang') !== 'en-gb') {
            throw new ExpectationException(
                'Language not specified in html tag',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }
    }

    /**
     * @Given the documents language is set to Welsh
     */
    public function theDocumentsLanguageIsSetToWelsh(): void
    {
        $htmlElement = $this->ui->getMink()->getSession()->getPage()->find('css', 'html');
        if ($htmlElement === null) {
            throw new ExpectationException(
                'Html tag not found',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }

        if ($htmlElement->getAttribute('lang') !== 'cy-gb') {
            throw new ExpectationException(
                'Language not specified in html tag',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }
    }

    /**
     * @Then the service homepage should be shown securely
     * @Then the viewer service homepage should be shown securely
     * @Then the actor service homepage should be shown securely
     */
    public function theViewerServiceHomepageShouldBeShownSecurely(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->assertHttps();
    }

    /**
     * @Then the service should redirect me to :startpage
     */
    public function theServiceShouldRedirectToGovUk(string $startpage): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->assertExactUrl($startpage);
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
        if (!array_key_exists($key, $this->responseJson)) {
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
        $this->ui->assertSession()->cookieExists('__Host-session');

        // could be moved to an assertion function in BaseContext but this is the *only* place this code will be used.
        /** @var ChromeDriver $driver */
        $driver  = $this->ui->getSession()->getDriver();
        $cookies = $driver->getCookies();

        array_walk($cookies, function (array $cookie) {
            if ($cookie['name'] === '__Host-session' && !$cookie['httpOnly']) {
                throw new ExpectationException(
                    'Unable to verify that the session cookie is "httpOnly"',
                    $this->ui->getSession()
                );
            }

            if ($cookie['name'] === '__Host-session' && !$cookie['secure']) {
                throw new ExpectationException(
                    'Unable to verify that the session cookie is "secure"',
                    $this->ui->getSession()
                );
            }
        });
    }

    /**
     * @Then /^I receive headers that block external indexing$/
     */
    public function iReceiveHeadersThatBlockExternalIndexing(): void
    {
        $session    = $this->ui->getSession();
        $xrobotstag = $session->getResponseHeader('X-Robots-Tag');

        Assert::assertNotNull($xrobotstag);
        Assert::assertStringContainsString('nofollow', $xrobotstag);
        Assert::assertStringContainsString('noindex', $xrobotstag);
    }

    /**
     * @Then /^I receive headers that cause the browser to not inform the destination site any URL information$/
     */
    public function iReceiveHeadersThatBlockURLInformation(): void
    {
        $session           = $this->ui->getSession();
        $referrerPolicyTag = $session->getResponseHeader('Referrer-Policy');

        Assert::assertNotNull($referrerPolicyTag);
        Assert::assertStringContainsString('same-origin', $referrerPolicyTag);
    }

    /**
     * @Then I am given a PDF file of the summary
     */
    public function IAmGivenAPDFFileOfTheSummary(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
    }

    /**
     * @Then I receive headers that block external iframe embedding
     */
    public function IAmNotAllowedToViewThisPageInAnIframe(): void
    {
        $session       = $this->ui->getSession();
        $xFrameOptions = $session->getResponseHeader('X-Frame-Options');

        Assert::assertNotNull($xFrameOptions);
        Assert::assertStringContainsString('deny', $xFrameOptions);
    }
}
