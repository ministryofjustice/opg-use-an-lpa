<?php

declare(strict_types=1);

namespace BehatTest\Http\Adapter\Guzzle6;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use Psr\Container\ContainerInterface;

class TestClientFactory
{

    public function __invoke(ContainerInterface $container)
    {
        $handlerStack = $container->get(HandlerStack::class);

        $guzzleClient = new Client(
            [
                'handler' => $handlerStack,
                'timeout' => 2,
                'http_errors' => false
            ]
        );

        return new ClientAdapter($guzzleClient);
    }
}
