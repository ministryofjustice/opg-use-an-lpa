<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Actor\Handler\LpaDashboardHandler;
use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ContextUtilities;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Common\Middleware\Session\SessionExpiryMiddlewareFactory;
use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\Session\EncryptedCookiePersistenceFactory;
use DI\Container;
use DI\Definition\AutowireDefinition;
use DI\Definition\Helper\FactoryDefinitionHelper;
use DI\Definition\Reference;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;
use PHPUnit\Framework\Assert;

/**
 * @property $traceId  The X-Amzn-Trace-Id that gets attached to incoming requests by the AWS LB
 */
class CommonContext implements Context
{
    use BaseUiContextTrait;

    private const SYSTEM_MESSAGE_SERVICE_GET_MESSAGES = 'SystemMessageService::getMessages';

    #[Given('I access the service home page')]
    public function iAccessTheServiceHomepage(): void
    {
        if ($this->base->container->get('config')['application'] === 'viewer') {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode($this->systemMessageData ?? []),
                    self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
                )
            );
        }

        $this->ui->visit($this->sharedState()->basePath . '/home');
    }

    #[Given('/^I am able to login$/')]
    public function iAmAbleToLogin(): void
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->fillField('triageEntry', 'yes');
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Sign in to your Use a lasting power of attorney account');
    }

    #[Then('I am given a session cookie')]
    public function iAmGivenASessionCookie(): void
    {
        $this->ui->assertSession()->cookieExists('__Host-session');
    }

    #[Given('/^I am on the contact us page$/')]
    public function iAmOnTheContactUsPage(): void
    {
        $this->ui->visit('/contact-us');
        $this->ui->assertPageAddress('/contact-us');
    }

    #[Then('/^I am on the cookie preferences page$/')]
    public function iAmOnTheCookiePreferencesPage(): void
    {
        $this->ui->assertPageAddress('/cookies');
    }

    #[Then('/^the link takes me to the call charges page$/')]
    public function theLinkTakesMeToTheCallChargesPage(): void
    {
        $link = $this->ui->getSession()->getPage()->findLink('Find out about call charges');
        assert::assertEquals('https://www.gov.uk/call-charges', $link->getAttribute('href'));
    }

    #[Given('/^I attach a tracing header to my requests$/')]
    public function iAttachATracingHeaderToMyRequests(): void
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

    #[Then('/^I see a (.*) cookie consent banner$/')]
    public function iCanSeeACookieConsentBanner(string $serviceName): void
    {
        $this->ui->assertPageAddress('/home');

        $this->ui->assertPageContainsText($serviceName);
        $this->ui->assertPageContainsText('Cookies on ' . $serviceName);
    }

    #[Then('/^I can see the contact us page$/')]
    public function iCanSeeTheContactUsPage(): void
    {
        $this->ui->assertPageAddress('/contact-us');
        $this->ui->assertPageContainsText('Contact us');
    }

    #[Then('/I choose (.*) and save my choice$/')]
    public function iChooseAnOptionAndSaveMyChoice($options): void
    {
        if ($options === 'Yes') {
            $this->ui->fillField('usageCookies', 'yes');
        } else {
            $this->ui->fillField('usageCookies', 'no');
        }
        $this->ui->pressButton('Save changes');
    }

    #[Then('/^I click on (.*) button$/')]
    public function iClickOnButton($button): void
    {
        $this->ui->assertPageContainsText($button);
        if ($button === 'Set cookie preferences') {
            $this->ui->clickLink($button);
        }
    }

    #[Then('/^I click on the view cookies link$/')]
    public function iClickOnViewCookies(): void
    {
        $this->ui->assertPageContainsText('View cookies');
        $this->ui->clickLink('View cookies');
    }

    #[Then('/^I expect to be on the Gov uk homepage$/')]
    public function iExpectToBeOnTheGovUkHomepage(): void
    {
        $this->ui->assertPageAddress('https://www.gov.uk');
    }

    #[Then('/^I have a cookie named cookie_policy$/')]
    public function iHaveACookieNamedSeenCookieMessage(): void
    {
        $this->ui->assertPageAddress('/cookies');

        $session = $this->ui->getSession();
        $seen    = $session->getCookie('cookie_policy');

        if ($seen === null) {
            throw new Exception('Cookies not set');
        }
    }

    #[Then('/^I am shown cookie preferences has been set$/')]
    public function iAmShownCookiePreferencesHasBeenSet(): void
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText(
            'You’ve set your cookie preferences. Go back to the page you were looking at.'
        );
        $this->ui->assertElementContains('h2[id=govuk-notification-banner-title]', '');
    }

    #[Given('/^I have seen the (.*) cookie banner$/')]
    public function iHaveSeenTheCookieBanner(string $serviceName): void
    {
        $this->iWantToViewALastingPowerOfAttorney();
        $this->iAccessTheServiceHomepage();
        $this->iCanSeeACookieConsentBanner($serviceName);
    }

    #[When('/^I can see the link to the call charges page$/')]
    public function iCanSeeTheLinkToTheCallChargesPage(): void
    {
        $link = $this->ui->getSession()->getPage()->findLink('Find out about call charges');
        assert::assertNotNull($link);
    }

    #[When('/^I navigate to the gov uk page$/')]
    public function iNavigateToTheGovUkPage(): void
    {
        $this->ui->clickLink('GOV.UK');
    }

    #[Given('/^I prefix a url with the welsh language code$/')]
    public function iPrefixAUrlWithTheWelshLanguageCode(): void
    {
        $this->sharedState()->basePath = '/cy';
    }

    #[When('/^I provide a wrong url that does not exist$/')]
    public function iProvideAWrongUrlThatDoesNotExist(): void
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->visit('/home/random');
    }

    #[When('/^I request to see the contact us details$/')]
    public function iRequestToSeeTheContactUsDetails(): void
    {
        $this->ui->clickLink('Contact us');
    }

    #[When('/^I request to view the accessibility statement$/')]
    public function iRequestToViewTheAccessibilityStatement(): void
    {
        $this->ui->clickLink('Accessibility statement');
    }

    #[Given('/^I request to view the content in english$/')]
    public function iRequestToViewTheContentInEnglish(): void
    {
        $this->ui->clickLink('English');
    }

    #[When('/^I request to view the content in welsh$/')]
    public function iRequestToViewTheContentInWelsh(): void
    {
        $this->ui->clickLink('Cymraeg');
    }

    #[Then('/^I see (.*) and (.*) button$/')]
    public function iSeeAcceptAllCookiesAndSetCookiePreferencesButton($button1, $button2): void
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText($button1);
        $this->ui->assertPageContainsText($button2);
        $this->ui->assertElementContainsText('button[value=accept]', 'Accept analytics cookies');
        $this->ui->assertElementContainsText('button[value=reject]', 'Reject analytics cookies');
    }

    #[Then('/^I see options (.*) and (.*) to accept analytics cookies$/')]
    public function iSeeOptionsToAcceptAnalyticsCookies($option1, $option2): void
    {
        $this->ui->assertPageContainsText('Do you want to accept analytics cookies');
        $this->ui->assertPageContainsText($option1);
        $this->ui->assertPageContainsText($option2);
        $this->ui->assertElementContains('input[id=usageCookies-1]', '');
        $this->ui->assertElementContains('input[id=usageCookies-2]', '');
    }

    #[Given('/^I set my cookie preferences$/')]
    public function iSetMyCookiePreferences(): void
    {
        $this->iClickOnViewCookies();
        $this->iSeeOptionsToAcceptAnalyticsCookies(
            'Yes',
            'No'
        );
        $this->iChooseAnOptionAndSaveMyChoice('Yes');
    }

    #[Then('/^I should be on the home page of the service$/')]
    public function iShouldBeOnTheHomePageOfTheService(): void
    {
        $this->ui->assertPageAddress('/home');
    }

    #[Then('/^I should be on the cookies page of the service$/')]
    public function iShouldBeOnTheCookiesPageOfTheService(): void
    {
        $this->ui->assertPageAddress('/cookies');
    }

    #[Then('/^I should be on the welsh home page of the service$/')]
    public function iShouldBeOnTheWelshHomepageOfTheService(): void
    {
        $this->ui->assertPageAddress('/cy/home');
    }

    #[Then('/^I should be shown an error page$/')]
    public function iShouldBeShownAnErrorPage(): void
    {
        $this->ui->assertPageContainsText('Sorry, there is a problem with the service');
    }

    #[When('/^I should be shown an error page with details$/')]
    public function iShouldBeShownAnErrorPageWithDetails(): void
    {
        $this->ui->assertPageAddress('/home/random');
        $this->ui->assertPageContainsText('Page not found');
    }

    #[Given('/^I want to use my lasting power of attorney$/')]
    public function iWantToUseMyLastingPowerOfAttorney(): void
    {
        // Not needed for this context
    }

    #[Given('/^I want to view a lasting power of attorney$/')]
    public function iWantToViewALastingPowerOfAttorney(): void
    {
        // Not needed for this context
    }

    /**
     * Relies on a previous context steps having set the last request value using
     * {@link BaseUiContextTrait::setLastRequest()}
     */
    #[Then('/^my outbound requests have attached tracing headers$/')]
    public function myOutboundRequestsHaveAttachedTracingHeaders(): void
    {
        $request = $this->apiFixtures->getLastRequest();
        Assert::assertTrue($request->hasHeader(strtolower('X-Amzn-Trace-Id')), 'No X-Amzn-Trace-Id header');
    }

    #[When('my session expires')]
    public function mySessionExpires(): void
    {
        /** @var Container $container */
        $container = $this->base->container;

        // change the session expiry to 1 (and we wait at the end to ensure expiry)
        $config                       = $container->get('config');
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
        $container->set(
            SessionExpiryMiddleware::class,
            new FactoryDefinitionHelper($container->get(SessionExpiryMiddlewareFactory::class))
        );

        // wait 2 seconds to ensure we expire
        sleep(2);
    }

    /**
     * Initialises default context state
     */
    #[BeforeScenario]
    public function setupDefaultContextVariables(): void
    {
        $this->sharedState()->basePath = '/';
    }

    #[When('/^I click on (.*) on the cookies page$/')]
    public function iClickOnLinkOnTheCookiesPage($link): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->assertPageAddress('/cookies');
        $this->ui->clickLink($link);
    }
}
