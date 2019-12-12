<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use Common\Service\ApiClient\Client as ApiClient;
use Common\Handler\Factory\HealthcheckHandlerFactory;
use Common\Handler\HealthcheckHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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