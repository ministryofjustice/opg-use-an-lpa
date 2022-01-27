<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Routing;

use Common\Middleware\Routing\ConditionalRoutingMiddleware;
use Interop\Container\ContainerInterface;
use Monolog\Test\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalRoutingMiddlewareTest extends TestCase
{
    private $containerProphecy;
    private $requestInterfaceProphecy;
    private $requestHandlerInterfaceProphecy;

    public function setUp(): void
    {
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->requestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->requestHandlerInterfaceProphecy = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function test_when_feature_flag_is_on_true_route_is_called()
    {
        $trueRouteProphecy = $this->prophesize(RequestHandlerInterface::class);
        $this->containerProphecy->get('TrueRoute')->shouldBeCalled()->willReturn($trueRouteProphecy);
        $this->containerProphecy->get('FalseRoute')->shouldNotBeCalled();
        $this->containerProphecy->get('config')->willReturn(['feature_flags' => ['Feature_Flag_Name' => true]]);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $trueRouteProphecy->handle(Argument::cetera())->shouldBeCalled();

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test */
    public function test_when_feature_flag_is_off_false_route_is_called()
    {
        $trueRouteProphecy = $this->prophesize(RequestHandlerInterface::class);

        $this->containerProphecy->get('TrueRoute')->shouldNotBeCalled();
        $this->containerProphecy->get('FalseRoute')->shouldBeCalled()->willReturn($trueRouteProphecy);
        $this->containerProphecy->get('config')->willReturn(['feature_flags' => ['Feature_Flag_Name' => false]]);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $trueRouteProphecy->handle(Argument::cetera())->shouldBeCalled();

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test */
    public function test_when_feature_flag_is_undefined_false_route_is_called()
    {
        $trueRouteProphecy = $this->prophesize(RequestHandlerInterface::class);

        $this->containerProphecy->get('TrueRoute')->shouldNotBeCalled();
        $this->containerProphecy->get('FalseRoute')->shouldBeCalled()->willReturn($trueRouteProphecy);
        $this->containerProphecy->get('config')->willReturn(['feature_flags' => []]);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $trueRouteProphecy->handle(Argument::cetera())->shouldBeCalled();

        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }

    /** @test  */
    public function test_when_feature_flag_is_not_defined_error_raised()
    {
        $this->containerProphecy->get('config')->willReturn([]);

        $sut = new ConditionalRoutingMiddleware(
            $this->containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $this->expectException(\UnexpectedValueException::class);
        $sut->process($this->requestInterfaceProphecy->reveal(), $this->requestHandlerInterfaceProphecy->reveal());
    }
}
