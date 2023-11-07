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
        $handlerStack = HandlerStack::create($mockHandler);
        $history      = Middleware::history($this->mockClientHistoryContainer);
        $handlerStack->push($history);
        $handlerStack->remove('http_errors');
        $handlerStack->remove('cookies');
        $handlerStack->remove('allow_redirects');
        $container->set(HandlerStack::class, $handlerStack);

        $this->apiFixtures = $mockHandler;

        $container->set(StylesService::class, new StylesService('./test/CommonTest/assets/stylesheets/pdf.css'));

        $this->prepareContext();
    }

    /**
     * Called after the PSR11 Container has been set into this Context
     */
    abstract protected function prepareContext(): void;
}
