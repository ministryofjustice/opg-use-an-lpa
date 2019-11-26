<?php

declare(strict_types=1);

namespace BehatTest\Http\Adapter\Guzzle6;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;

class TestClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $handlerStack = HandlerStack::create($container->get(MockHandler::class));
        $guzzleClient = new Client([ 'handler' => $handlerStack ]);

        return new ClientAdapter($guzzleClient);
    }
}