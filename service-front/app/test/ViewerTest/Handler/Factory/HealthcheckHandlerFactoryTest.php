<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use PHPUnit\Framework\TestCase;
use Viewer\Handler\Factory\HealthcheckHandlerFactory;
use Viewer\Handler\HealthcheckHandler;
use Psr\Container\ContainerInterface;
use Viewer\Service\ApiClient\Client as ApiClient;

class HealthcheckHandlerFactoryTest extends TestCase
{
    public function testItCreatesAHealthcheckHandler()
    {
        $factory = new HealthcheckHandlerFactory();

        $apiClient = $this->prophesize(ApiClient::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ApiClient::class)
            ->willReturn($apiClient->reveal());
        $container->get('config')
            ->willReturn(['version' => 'dev']);

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HealthcheckHandler::class, $handler);
    }
}