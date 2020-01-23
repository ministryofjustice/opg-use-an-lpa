<?php

declare(strict_types=1);

namespace BehatTest\Http\Adapter\Guzzle6;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use Psr\Container\ContainerInterface;

class TestClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new ClientAdapter($container->get(Client::class));
    }
}