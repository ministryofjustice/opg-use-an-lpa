<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\AbstractApiException;
use App\Exception\NotFoundException;
use App\Middleware\ProblemDetailsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Zend\Diactoros\Response\JsonResponse;

class ProblemDetailsMiddlewareTest extends TestCase
{
    public function testProcessSuccess()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $delegateProphecy->handle($requestProphecy->reveal())
            ->willReturn($responseProphecy->reveal());

        $middleware = new ProblemDetailsMiddleware();
        $response = $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessFailure()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $exception = new NotFoundException('Exception message');

        $delegateProphecy->handle($requestProphecy->reveal())
            ->willThrow($exception);

        $middleware = new ProblemDetailsMiddleware();

        /** @var JsonResponse $response */
        $response = $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $data = $response->getPayload();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Exception message', $data['details']);
    }
}
