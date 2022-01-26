<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Routing;

use Common\Middleware\Routing\ConditionalRoutingMiddleware;
use Mezzio\Middleware\LazyLoadingMiddleware;
use Monolog\Test\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\DI\Config\ContainerWrapper;

class ConditionalRoutingMiddlewareTest extends TestCase {
    /** @test */
    public function test_when_feature_flag_is_on_true_route_is_called()
    {
        $trueRouteProphecy = $this->prophesize(RequestHandlerInterface::class);

        $containerProphecy = $this->prophesize(ContainerWrapper::class);
        $containerProphecy->get('TrueRoute')->shouldBeCalled()->willReturn($trueRouteProphecy);
        $containerProphecy->get('FalseRoute')->shouldNotBeCalled();
        $containerProphecy->get('config')->willReturn(['feature_flags' => ['Feature_Flag_Name' => true]]);



        $sut = new ConditionalRoutingMiddleware(
            $containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $requestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestHandlerInterfaceProphecy = $this->prophesize(RequestHandlerInterface::class);

        $trueRouteProphecy->handle(Argument::cetera())->shouldBeCalled();

        $sut->process($requestInterfaceProphecy->reveal(), $requestHandlerInterfaceProphecy->reveal());
    }

    /** @test */
    public function test_when_feature_flag_is_off_false_route_is_called()
    {
        $trueRouteProphecy = $this->prophesize(RequestHandlerInterface::class);

        $containerProphecy = $this->prophesize(ContainerWrapper::class);
        $containerProphecy->get('TrueRoute')->shouldNotBeCalled();
        $containerProphecy->get('FalseRoute')->shouldBeCalled()->willReturn($trueRouteProphecy);
        $containerProphecy->get('config')->willReturn(['feature_flags'=> ['Feature_Flag_Name'=> false]]);



        $sut = new ConditionalRoutingMiddleware(
            $containerProphecy->reveal(),
            'Feature_Flag_Name',
            'TrueRoute',
            'FalseRoute'
        );

        $requestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestHandlerInterfaceProphecy = $this->prophesize(RequestHandlerInterface::class);

        $trueRouteProphecy->handle(Argument::cetera())->shouldBeCalled();

        $sut->process($requestInterfaceProphecy->reveal(), $requestHandlerInterfaceProphecy->reveal());
    }
}
