<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Exception\UnauthorizedException;
use App\Middleware\UserIdentificationMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Message\ResponseInterface;

class UserIdentificationMiddlewareTest extends TestCase
{
    /** @test */
    public function it_correctly_produces_a_successful_response()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $requestProphecy->getHeader('User-Token')
            ->willReturn(['test-token-123']);

        $requestProphecy->withAttribute('actor-id', 'test-token-123')
            ->willReturn($requestProphecy->reveal());

        $delegateProphecy->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $middleware = new UserIdentificationMiddleware();

        $response = $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function it_throws_an_unauthorized_exception_when_no_user_token()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $requestProphecy->getHeader('User-Token')
            ->willReturn([]);

        $middleware = new UserIdentificationMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('User-Token not specified or invalid');

        $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
