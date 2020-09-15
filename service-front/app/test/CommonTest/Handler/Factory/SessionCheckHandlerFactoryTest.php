<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use Common\Handler\Factory\SessionCheckHandlerFactory;
use Common\Handler\SessionCheckHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class SessionCheckHandlerFactoryTest extends TestCase
{
    public function testItCreatesASessionCheckHandler()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn(
                [
                    'session' => [
                        'expires' => 1200
                    ],
                ]
            );

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $factory = new SessionCheckHandlerFactory();
        $sessionCheckHandler = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(SessionCheckHandler::class, $sessionCheckHandler);
    }

    /**
     * @test
     */
    public function testThrowsExceptionMissingConfigValue()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn([]);

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $factory = new SessionCheckHandlerFactory();

        $this->expectException(\RuntimeException::class);

        $sessionCheckHandler = $factory($containerProphecy->reveal());
    }
}
