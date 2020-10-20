<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use Actor\Handler\Factory\ActorSessionCheckHandlerFactory;
use Actor\Handler\ActorSessionCheckHandler;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandlerFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $containerProphecy;

    public function setup()
    {
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->containerProphecy
            ->get(TemplateRendererInterface::class)
            ->willReturn($this->prophesize(TemplateRendererInterface::class)->reveal());
        $this->containerProphecy
            ->get(UrlHelper::class)
            ->willReturn($this->prophesize(UrlHelper::class)->reveal());
        $this->containerProphecy
            ->get(AuthenticationInterface::class)
            ->willReturn($this->prophesize(AuthenticationInterface::class)->reveal());
        $this->containerProphecy
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $this->containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());
    }

    /**
     * @test
     */
    public function testItCreatesASessionCheckHandler()
    {
        $this->containerProphecy->get('config')
            ->willReturn(
                [
                    'session' => [
                        'expires' => 1200
                    ],
                ]
            );

        $factory = new ActorSessionCheckHandlerFactory();
        $sessionCheckHandler = $factory($this->containerProphecy->reveal());

        $this->assertInstanceOf(ActorSessionCheckHandler::class, $sessionCheckHandler);
    }

    /**
     * @test
     */
    public function testThrowsExceptionMissingConfigValue()
    {
        $this->containerProphecy->get('config')
            ->willReturn([]);

        $factory = new ActorSessionCheckHandlerFactory();

        $this->expectException(\RuntimeException::class);

        $sessionCheckHandler = $factory($this->containerProphecy->reveal());
    }
}
