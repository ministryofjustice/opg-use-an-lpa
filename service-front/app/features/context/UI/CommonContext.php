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
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;

/**
 * Class CommonContext
 *
 * @package BehatTest\Context\UI
 *
 * @property $traceId The X-Amzn-Trace-Id that gets attached to incoming requests by the AWS LB
 */
class CommonContext implements Context
{
    use BaseUiContextTrait;

    /**
     * @Given I access the service homepage
     */
    public function iAccessTheServiceHomepage(): void
    {
        $this->ui->iAmOnHomepage();
    }

    /**
     * @Then I am given a session cookie
     */
    public function iAmGivenASessionCookie()
    {
        $this->ui->assertSession()->cookieExists('session');
    }

    /**
     * @Given I attach a tracing header to my requests
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
     * @Then my outbound requests have attached tracing headers
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

        // change the session expiry to 1 (i.e. we wait at the end to ensure expiry)
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
}
