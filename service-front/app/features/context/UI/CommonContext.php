<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Actor\Handler\LpaDashboardHandler;
use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
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
 * @property $traceId  The X-Amzn-Trace-Id that gets attached to incoming requests by the AWS LB
 * @property $basePath The base part of the URL, typically '/' but could be a language prefix i.e. '/cy'
 */
class CommonContext implements Context
{
    use BaseUiContextTrait;

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
     * @Given /^I am on the contact us page$/
     */
    public function iAmOnTheContactUsPage()
    {
        $this->ui->visit('/contact-us');
        $this->ui->assertPageAddress('/contact-us');
    }

    /**
     * @Then /^I am on the cookie preferences page$/
     */
    public function iAmOnTheCookiePreferencesPage()
    {
        $this->ui->assertPageAddress('/cookies');
    }

    /**
     * @Then /^I am taken to the call charges page$/
     */
    public function iAmTakenToTheCallChargesPage()
    {
        $this->ui->assertPageAddress('https://www.gov.uk/call-charges');
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
     * @Then /^I see a (.*) cookie consent banner$/
     */
    public function iCanSeeACookieConsentBanner($serviceName)
    {
        $this->ui->assertPageAddress('/home');

        //var_dump($serviceName); die;
        $this->ui->assertPageContainsText($serviceName);
        $this->ui->assertPageContainsText('Cookies on ' . $serviceName);
    }

    /**
     * @Then /^I can see the contact us page$/
     */
    public function iCanSeeTheContactUsPage()
    {
        $this->ui->assertPageAddress('/contact-us');
        $this->ui->assertPageContainsText('Contact us');
    }

    /**
     * @Then /I choose (.*) and save my choice$/
     */
    public function iChooseAnOptionAndSaveMyChoice($options)
    {
        if ($options === 'Yes') {
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
        }
    }

    /**
     * @Then /^I click on the view cookies link$/
     */
    public function iClickOnViewCookies()
    {
        $this->ui->assertPageContainsText('View cookies');
        $this->ui->clickLink('View cookies');
    }

    /**
     * @Then /^I expect to be on the Gov uk homepage$/
     */
    public function iExpectToBeOnTheGovUkHomepage()
    {
        $this->ui->assertPageAddress('https://www.gov.uk');
    }

    /**
     * @Then /^I have a cookie named cookie_policy$/
     */
    public function iHaveACookieNamedSeenCookieMessage()
    {
        $this->ui->assertPageAddress('/cookies');

        $session = $this->ui->getSession();
        $seen = $session->getCookie('cookie_policy');

        if ($seen === null) {
            throw new \Exception('Cookies not set');
        }
    }

    /**
     * @Then /^I am shown cookie preferences has been set$/
     */
    public function iAmShownCookiePreferencesHasBeenSet()
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText("You’ve set your cookie preferences. Go back to the page you were looking at.");
        $this->ui->assertElementContains('h2[id=govuk-notification-banner-title]', '');
    }

    /**
     * @Given /^I have seen the (.*) cookie banner$/
     */
    public function iHaveSeenTheCookieBanner($serviceName)
    {
        $this->iWantToViewALastingPowerOfAttorney();
        $this->iAccessTheServiceHomepage();
        $this->iCanSeeACookieConsentBanner($serviceName);
    }

    /**
     * @When /^I navigate to the call charges page$/
     */
    public function iNavigateToTheFeedbackPage()
    {
        $this->ui->clickLink('Find out about call charges');
    }

    /**
     * @When /^I navigate to the gov uk page$/
     */
    public function iNavigateToTheGovUkPage()
    {
        $this->ui->clickLink('GOV.UK');
    }

    /**
     * @Given /^I prefix a url with the welsh language code$/
     */
    public function iPrefixAUrlWithTheWelshLanguageCode()
    {
        $this->basePath = '/cy';
    }

    /**
     * @When /^I provide a wrong url that does not exist$/
     */
    public function iProvideAWrongUrlThatDoesNotExist()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->visit('/home/random');
    }

    /**
     * @When /^I request to see the contact us details$/
     */
    public function iRequestToSeeTheContactUsDetails()
    {
        $this->ui->clickLink('Contact us');
    }

    /**
     * @When /^I request to view the accessibility statement$/
     */
    public function iRequestToViewTheAccessibilityStatement()
    {
        $this->ui->clickLink('Accessibility statement');
    }

    /**
     * @Given /^I request to view the content in english$/
     */
    public function iRequestToViewTheContentInEnglish()
    {
        $this->ui->clickLink('English');
    }

    /**
     * @When /^I request to view the content in welsh$/
     */
    public function iRequestToViewTheContentInWelsh()
    {
        $this->ui->clickLink('Cymraeg');
    }

    /**
     * @Then /^I see (.*) and (.*) button$/
     */
    public function iSeeAcceptAllCookiesAndSetCookiePreferencesButton($button1, $button2)
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText($button1);
        $this->ui->assertPageContainsText($button2);
        $this->ui->assertElementContainsText('button[value=accept]', 'Accept analytics cookies');
        $this->ui->assertElementContainsText('button[value=reject]', 'Reject analytics cookies');
    }

    /**
     * @Then /^I see options (.*) and (.*) to accept analytics cookies$/
     */
    public function iSeeOptionsToAcceptAnalyticsCookies($option1, $option2)
    {
        $this->ui->assertPageContainsText("Do you want to accept analytics cookies");
        $this->ui->assertPageContainsText($option1);
        $this->ui->assertPageContainsText($option2);
        $this->ui->assertElementContains('input[id=usageCookies-1]', '');
        $this->ui->assertElementContains('input[id=usageCookies-2]', '');
    }

    /**
     * @Given /^I set my cookie preferences$/
     */
    public function iSetMyCookiePreferences()
    {
        $this->iClickOnViewCookies();
        $this->iSeeOptionsToAcceptAnalyticsCookies(
            'Yes',
            'No'
        );
       $this->iChooseAnOptionAndSaveMyChoice('Yes');
    }

    /**
     * @Then /^I should be on the home page of the service$/
     */
    public function iShouldBeOnTheHomePageOfTheService()
    {
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @Then /^I should be on the cookies page of the service$/
     */
    public function iShouldBeOnTheCookiesPageOfTheService()
    {
        $this->ui->assertPageAddress('/cookies');
    }

    /**
     * @Then /^I should be on the welsh home page of the service$/
     */
    public function iShouldBeOnTheWelshHomepageOfTheService()
    {
        $this->ui->assertPageAddress('/cy/home');
    }

    /**
     * @Then /^I should be shown an error page$/
     */
    public function iShouldBeShownAnErrorPage()
    {
        $this->ui->assertPageContainsText('Sorry, there is a problem with the service');
    }

    /**
     * @When /^I should be shown an error page with details$/
     */
    public function iShouldBeShownAnErrorPageWithDetails()
    {
        $this->ui->assertPageAddress('/home/random');
        $this->ui->assertPageContainsText('Page not found');
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

        // wait 2 seconds to ensure we expire
        sleep(2);
    }

    /**
     * Initialises default context state
     *
     * @beforeScenario
     */
    public function setupDefaultContextVariables(): void
    {
        $this->basePath = '/';
    }

    /**
     * @When /^I click on (.*) on the cookies page$/
     */
    public function iClickOnLinkOnTheCookiesPage($link): void
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->clickLink($link);
    }

}
