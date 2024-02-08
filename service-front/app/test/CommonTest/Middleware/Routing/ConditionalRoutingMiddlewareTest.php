<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Routing;

use Common\Middleware\Routing\ConditionalRoutingMiddleware;
use Interop\Container\ContainerInterface;
use Mezzio\MiddlewareFactoryInterface;
use Monolog\Test\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

use function Laminas\Stratigility\middleware;

class ConditionalRoutingMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|MiddlewareFactoryInterface $middlewareFactoryProphecy;
    private ObjectProphecy|ContainerInterface $containerProphecy;
    private ObjectProphecy|ServerRequestInterface $requestInterfaceProphecy;
    private ObjectProphecy|RequestHandlerInterface $requestHandlerInterfaceProphecy;

    public function setUp(): void
    {
        $this->middlewareFactoryProphecy       = $this->prophesize(MiddlewareFactoryInterface::class);
        $this->containerProphecy               = $this->prophesize(ContainerInterface::class);
        $this->requestInterfaceProphecy        = $this->prophesize(ServerRequestInterface::class);
        $this->requestHandlerInterfaceProphecy = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function test_when_feature_flag_is_on_true_route_is_called(): void
    {
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->shouldBeCalled();

        $this->containerProphecy->get('config')->willReturn(['feature_flags' => ['Feature_Flag_Name' => true]]);

        $this->middlewareFactoryProphecy->prepare('TrueRoute')->shouldBeCalled()->willReturn($middlewareProphecy);
        $this->middlewareFactoryProphecy->prepare('FalseRoute')->shouldNotBeCalled();

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            $this->middlewareFactoryProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test */
    public function test_when_feature_flag_is_off_false_route_is_called(): void
    {
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->shouldBeCalled();

        $this->containerProphecy->get('config')->willReturn(['feature_flags' => ['Feature_Flag_Name' => false]]);

        $this->middlewareFactoryProphecy->prepare('TrueRoute')->shouldNotBeCalled();
        $this->middlewareFactoryProphecy->prepare('FalseRoute')->shouldBeCalled()->willReturn($middlewareProphecy);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            $this->middlewareFactoryProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test */
    public function test_when_feature_flag_is_undefined_false_route_is_called(): void
    {
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->shouldBeCalled();

        $this->containerProphecy->get('config')->willReturn(['feature_flags' => []]);

        $this->middlewareFactoryProphecy->prepare('TrueRoute')->shouldNotBeCalled();
        $this->middlewareFactoryProphecy->prepare('FalseRoute')->shouldBeCalled()->willReturn($middlewareProphecy);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            $this->middlewareFactoryProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test  */
    public function test_when_feature_flag_is_not_defined_error_raised(): void
    {
        $this->containerProphecy->get('config')->willReturn([]);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            $this->middlewareFactoryProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $this->expectException(UnexpectedValueException::class);
        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }
}
