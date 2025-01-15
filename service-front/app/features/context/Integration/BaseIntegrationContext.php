<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Context;
use Common\Service\Pdf\StylesService;
use DI\Container;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

abstract class BaseIntegrationContext implements Context, Psr11AwareContext
{
    protected ContainerInterface|Container $container;
    public MockHandler $apiFixtures;
    public array $mockClientHistoryContainer = [];

    /**
     * @inheritDoc
     */
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        //Create handler stack and push to container
        $mockHandler  = $container->get(MockHandler::class);
        $handlerStack = new HandlerStack($mockHandler);
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
        $history = Middleware::history($this->mockClientHistoryContainer);
        $handlerStack->push($history);
        $container->set(HandlerStack::class, $handlerStack);

        $this->apiFixtures = $mockHandler;

        $container->set(StylesService::class, new StylesService('./test/CommonTest/assets/stylesheets/pdf.css'));

        $this->prepareContext();
    }

    /**
     * Called after the PSR11 Container has been set into this Context
     *
     * WARNING: do not use this method to initialise global context variables of services
     * pulled from the container as this breaks Feature Flag in subtle ways.
     */
    abstract protected function prepareContext(): void;
}
