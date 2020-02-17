<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Actor\Handler\LpaDashboardHandler;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Lpa\LpaService;
use DI\Container;
use DI\Definition\AutowireDefinition;
use DI\Definition\Helper\FactoryDefinitionHelper;

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
}