<?php

declare(strict_types=1);

namespace BehatTest\Http\Adapter\Guzzle6;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use Psr\Container\ContainerInterface;

class TestClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $guzzleClient = new Client([ 'handler' => $container->get(HandlerStack::class) ]);

        return new ClientAdapter($guzzleClient);
    }
}