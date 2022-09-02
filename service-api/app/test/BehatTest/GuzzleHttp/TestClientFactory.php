<?php

declare(strict_types=1);

namespace BehatTest\GuzzleHttp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Psr\Container\ContainerInterface;

class TestClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $handlerStack = HandlerStack::create($container->get(MockHandler::class));
        return new Client([ 'handler' => $handlerStack, 'timeout' => 2 ]);
    }
}
