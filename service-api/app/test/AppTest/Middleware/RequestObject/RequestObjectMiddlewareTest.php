<?php

declare(strict_types=1);

namespace AppTest\Middleware\RequestObject;

use App\Middleware\RequestObject\RequestObjectMiddleware;
use App\Middleware\RequestObject\RequestParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestObjectMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_attaches_a_request_parser(): void
    {
        $requestParserProphecy = $this->prophesize(RequestParser::class);
        $requestParserProphecy
            ->setRequestData(Argument::type('array'))
            ->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->willReturn(['data']);
        $requestProphecy
            ->withAttribute('requestObject', $requestParserProphecy->reveal())
            ->willReturn($requestProphecy->reveal())
            ->shouldBeCalled();

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestHandlerProphecy->handle($requestProphecy->reveal())->shouldBeCalled();

        $middleware = new RequestObjectMiddleware($requestParserProphecy->reveal());

        $middleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }
}
