<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Actor\Handler\LpaDashboardHandler;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\Session\EncryptedCookiePersistenceFactory;
use DI\Container;
use DI\Definition\AutowireDefinition;
use DI\Definition\Helper\FactoryDefinitionHelper;
use DI\Definition\Reference;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;

/**
 * Class CommonContext
 *
 * @package BehatTest\Context\UI
 *
 * @property $traceId The X-Amzn-Trace-Id that gets attached to incoming requests by the AWS LB
 * @property $basePath The base part of the URL, typically '/' but could be a language prefix i.e. '/cy'
 */
class CommonContext implements Context
{
    use BaseUiContextTrait;

    /**
     * Initialises default context state
     *
     * @beforeScenario
     */
    public function _setupDefaultContextVariables(): void
    {
        $this->basePath = '/';
    }

    /**
     * @Given I access the service home page
     */
    public function iAccessTheServiceHomepage(): void
    {
        $this->ui->visit($this->basePath . '/home');
    }

    /**
     * @Given /^I am able to login$/
     */
    public function iAmAbleToLogin()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->fillField('triageEntry', 'yes');
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Sign in to your Use a lasting power of attorney account');
    }

    /**
     * @Then I am given a session cookie
     */
    public function iAmGivenASessionCookie()
    {
        $this->ui->assertSession()->cookieExists('session');
    }

    /**
     * @Then /^I am on the cookie preferences page$/
     */
    public function iAmOnTheCookiePreferencesPage()
    {
        $this->ui->assertPageAddress('/cookies');
    }

    /**
     * @Given /^I attach a tracing header to my requests$/
     */
    public function iAttachATracingHeaderToMyRequests()
    {
        // This horrible container manipulation brought to you by:
        // https://github.com/minkphp/MinkBrowserKitDriver/issues/79
        //
        // Hopefully the PR for it will get merged in which case the feature test "An inbound tracing header
        // is attached to outbound requests" can be reworked to remove all this nasty.
        /** @var Container $container */
        $container = $this->base->container;
        $container->set(Client::class, new FactoryDefinitionHelper($container->get(ClientFactory::class)));
        $container->set(LpaService::class, new AutowireDefinition(LpaService::class));
        $container->set(LpaDashboardHandler::class, new AutowireDefinition(LpaDashboardHandler::class));

        $this->traceId = 'Root=1-1-11';

        $this->ui->getSession()->setRequestHeader('X-Amzn-Trace-Id', $this->traceId);
    }

    /**
     * @Then /^I see a cookie consent banner$/
     */
    public function iCanSeeACookieConsentBanner()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Tell us whether you accept cookies');
    }

    /**
     * @Then /I choose an (.*) and save my choice$/
     */
    public function iChooseAnOptionAndSaveMyChoice($options)
    {
        if ($options === 'Use cookies that measure my website use') {
            $this->ui->fillField('usageCookies', 'yes');
        } else {
            $this->ui->fillField('usageCookies', 'no');
        }
        $this->ui->pressButton('Save changes');
    }

    /**
     * @Given /^I chose to ignore setting cookies and I am on the dashboard page$/
     */
    public function iChoseToIgnoreSettingCookiesAndIAmOnTheDashboardPage()
    {
        $this->iAmAbleToLogin();

        $userEmail = 'test@test.com';
        $password = 'pa33w0rd';
        $userActive = true;
        $userId = '123';

        $this->ui->fillField('email', $userEmail);
        $this->ui->fillField('password', $password);

        if ($userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'Id' => $userId,
                                'Email' => $userEmail,
                                'LastLogin' => '2020-01-01',
                            ]
                        )
                    )
                );

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->get('/v1/lpas')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->pressButton('Sign in');
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^I click on (.*) button$/
     */
    public function iClickOnButton($button)
    {
        $this->ui->assertPageContainsText($button);
        if ($button === 'Set cookie preferences') {
            $this->ui->clickLink($button);
        } else {
            $this->ui->pressButton($button);
        }
    }

    /**
     * @Then /I have a cookie named (.*)$/
     */
    public function iHaveACookieNamedSeenCookieMessage()
    {
        $this->ui->assertPageAddress('/home');

        $session = $this->ui->getSession();

        // retrieving response headers:
        $cookies = $session->getResponseHeaders()['Set-Cookie'];

        if (!$cookies === null) {
            foreach ($cookies as $value) {
                if (strstr($value, 'seen-cookie-message')) {
                    assertContains('true', $value);
                } else {
                    throw new \Exception('Cookie named seen-cookie-message not found in the response header');
                }
            }
        }
    }

    /**
     * @Given /^I have seen the cookie banner$/
     */
    public function iHaveSeenTheCookieBanner()
    {
        $this->iWantToViewALastingPowerOfAttorney();
        $this->iAccessTheServiceHomepage();
        $this->iCanSeeACookieConsentBanner();
    }

    /**
     * @Given /^I prefix a url with the welsh language code$/
     */
    public function iPrefixAUrlWithTheWelshLanguageCode()
    {
        $this->basePath = '/cy';
    }

    /**
     * @Then /^I see (.*) and (.*) button$/
     */
    public function iSeeAcceptAllCookiesAndSetCookiePreferencesButton($button1, $button2)
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText($button1);
        $this->ui->assertPageContainsText($button2);
        $this->ui->assertElementContainsText('button[name=accept-all-cookies]', 'Accept all cookies');
        $this->ui->assertElementContainsText('a[name=set-cookie-preferences]', 'Set cookie preferences');
    }

    /**
     * @Then /^I see options to (.*) and (.*)$/
     */
    public function iSeeOptionsToSetAndUnsetCookiesThatMeasureMyWebsiteUse($option1, $option2)
    {
        $this->ui->assertPageContainsText("Cookies that measure website use");
        $this->ui->assertElementContains('input[id=usageCookies-1]', '');
        $this->ui->assertElementContains('input[id=usageCookies-2]', '');
    }

    /**
     * @Given /^I set my cookie preferences$/
     */
    public function iSetMyCookiePreferences()
    {
        $this->iClickOnButton('Set cookie preferences');
        $this->iSeeOptionsToSetAndUnsetCookiesThatMeasureMyWebsiteUse(
            'Use cookies that measure my website use',
            'Do not use cookies that measure my website use'
        );
        $this->iChooseAnOptionAndSaveMyChoice('Use cookies that measure my website use');
    }

    /**
     * @Then /^I should be on the home page of the service$/
     */
    public function iShouldBeOnTheHomePageOfTheService()
    {
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @Then /^I should be on the welsh home page of the service$/
     */
    public function iShouldBeOnTheWelshHomepageOfTheService()
    {
        $this->ui->assertPageAddress('/cy/home');
    }

    /**
     * @Then /^I should not see a cookie banner$/
     */
    public function iShouldNotSeeACookieBanner()
    {
        $this->ui->assertPageAddress('/home');
        $cookieBannerDisplay = $this->ui->getSession()->getPage()->find('css', '.cookie-banner--show');
        if ($cookieBannerDisplay === null) {
            $this->ui->assertResponseNotContains('cookie-banner--show');
        }
    }

    /**
     * @Given /^I want to use my lasting power of attorney$/
     */
    public function iWantToUseMyLastingPowerOfAttorney()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I want to view a lasting power of attorney$/
     */
    public function iWantToViewALastingPowerOfAttorney()
    {
        // Not needed for this context
    }

    /**
     * @Then /^my outbound requests have attached tracing headers$/
     *
     * Relies on a previous context steps having set the last request value using
     * {@link BaseUiContextTrait::setLastRequest()}
     */
    public function myOutboundRequestsHaveAttachedTracingHeaders()
    {
        $request = $this->getLastRequest();
        $request->getRequest()->assertHasHeader(strtolower('X-Amzn-Trace-Id'));
    }

    /**
     * @When my session expires
     */
    public function mySessionExpires()
    {
        /** @var Container $container */
        $container = $this->base->container;

        // change the session expiry to 1 (and we wait at the end to ensure expiry)
        $config = $container->get('config');
        $config['session']['expires'] = 1;
        $container->set('config', $config);

        // reset the dependency chain so the new config value is respected
        $container->set(
            SessionPersistenceInterface::class,
            new Reference(EncryptedCookiePersistence::class)
        );
        $container->set(
            EncryptedCookiePersistence::class,
            new FactoryDefinitionHelper($container->get(EncryptedCookiePersistenceFactory::class))
        );
        $container->set(
            SessionMiddleware::class,
            new FactoryDefinitionHelper($container->get(SessionMiddlewareFactory::class))
        );

        // wait 1 to ensure we expire
        sleep(1);
    }

    /**
     * @When /^I navigate to the gov uk page$/
     */
    public function iNavigateToTheGovUkPage()
    {
        $this->ui->clickLink('GOV.UK');
    }

    /**
     * @Then /^I expect to be on the Gov uk homepage$/
     */
    public function iExpectToBeOnTheGovUkHomepage()
    {
        $this->ui->assertPageAddress('https://www.gov.uk');
    }
}
