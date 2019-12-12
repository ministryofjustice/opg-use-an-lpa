<?php

declare(strict_types=1);

namespace App\Test\Handler\Factory;

use PHPUnit\Framework\TestCase;
use App\Handler\Factory\HealthcheckHandlerFactory;
use App\Handler\HealthcheckHandler;
use Psr\Container\ContainerInterface;
use Aws\DynamoDb\DynamoDbClient;
use App\DataAccess\Repository\LpasInterface;

class HealthcheckHandlerFactoryTest extends TestCase
{
    public function testItCreatesAHealthcheckHandler()
    {
        $factory = new HealthcheckHandlerFactory();

        $dbClient = $this->prophesize(DynamoDbClient::class);
        $lpaInterface = $this->prophesize(LpasInterface::class);
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')
            ->willReturn(['version' => 'dev']);
        $container->get(DynamoDbClient::class)
        ->willReturn($dbClient->reveal());
        $container->get(LpasInterface::class)
            ->willReturn($lpaInterface->reveal());

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HealthcheckHandler::class, $handler);
    }
}