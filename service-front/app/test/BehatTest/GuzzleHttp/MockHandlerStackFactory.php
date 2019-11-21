<?php

declare(strict_types=1);

namespace BehatTest\GuzzleHttp;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Psr\Container\ContainerInterface;

class MockHandlerStackFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return HandlerStack::create($container->get(MockHandler::class));
    }
}