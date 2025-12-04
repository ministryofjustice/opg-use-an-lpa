<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Aws\DynamoDb\Marshaler;
use Behat\Behat\Context\Context;
use DI\Container;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

abstract class BaseIntegrationContext implements Context, Psr11AwareContext
{
    protected ContainerInterface|Container $container;
    public MockHandler $apiFixtures;

    /**
     * @var array{'request': RequestInterface}[] $history
     */
    public array $history;

    /**
     * @inheritDoc
     */
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
        $this->history   = [];

        $mockHandler  = $container->get(MockHandler::class);
        $handlerStack = new HandlerStack($mockHandler);
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
        $handlerStack->push(Middleware::history($this->history));

        $client = new Client(['handler' => $handlerStack]);
        $container->set(Client::class, $client);
        $container->set(ClientInterface::class, $client);

        $this->apiFixtures = $mockHandler;

        $this->prepareContext();
    }

    public function printHistory(): void
    {
        foreach ($this->history as $transaction) {
            echo $transaction['request']->getMethod() . ' ' . $transaction['request']->getUri() . "\n";
        }
    }

    /**
     * Convert a key/value array to a correctly marshaled AwsResult structure.
     *
     * AwsResult data is in a special array format that tells you
     * what datatype things are. This function creates that data structure.
     */
    protected function marshalAwsResultData(array $input): array
    {
        $marshaler = new Marshaler();

        return $marshaler->marshalItem($input);
    }

    /**
     * Called after the PSR11 Container has been set into this Context
     */
    abstract protected function prepareContext(): void;
}
