<?php

declare(strict_types=1);

namespace App\Test\Handler\Factory;

use PHPUnit\Framework\TestCase;
use App\Handler\Factory\HealthcheckHandlerFactory;
use App\Handler\HealthcheckHandler;
use Psr\Container\ContainerInterface;
use App\DataAccess\Repository\ActorCodesInterface;
use App\DataAccess\Repository\LpasInterface;

class HealthcheckHandlerFactoryTest extends TestCase
{
    public function testItCreatesAHealthcheckHandler()
    {
        $factory = new HealthcheckHandlerFactory();

        $actorCodes = $this->prophesize(ActorCodesInterface::class);
        $lpaInterface = $this->prophesize(LpasInterface::class);
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')
            ->willReturn(['version' => 'dev']);
        $container->get(ActorCodesInterface::class)
        ->willReturn($actorCodes->reveal());
        $container->get(LpasInterface::class)
            ->willReturn($lpaInterface->reveal());

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HealthcheckHandler::class, $handler);
    }
}