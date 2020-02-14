<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Logging;

use Common\Middleware\Logging\RequestTracingMiddleware;
use Common\Service\Container\ModifiableContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RequestTracingMiddlewareTest extends TestCase
{
    /** @test */
    public function it_sets_a_trace_attribute_if_set_as_a_header(): void
    {
        $containerProphecy = $this->prophesize(ModifiableContainerInterface::class);
        $containerProphecy->setValue('trace-id', 'Root=1-1-11')->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader('x-amzn-trace-id')->willReturn(['Root=1-1-11']);
        $requestProphecy->withAttribute('trace-id', 'Root=1-1-11')->willReturn($requestProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rtm = new RequestTracingMiddleware($containerProphecy->reveal());
        $response = $rtm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function trace_id_is_blank_if_no_header(): void
    {
        $containerProphecy = $this->prophesize(ModifiableContainerInterface::class);
        $containerProphecy->setValue('trace-id', '')->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader('x-amzn-trace-id')->willReturn([]);
        $requestProphecy->withAttribute('trace-id', '')->willReturn($requestProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rtm = new RequestTracingMiddleware($containerProphecy->reveal());
        $response = $rtm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
