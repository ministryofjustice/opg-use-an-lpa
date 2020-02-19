<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\NotFoundException;
use App\Middleware\ProblemDetailsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\JsonResponse;

class ProblemDetailsMiddlewareTest extends TestCase
{
    /** @test */
    public function it_correctly_handles_a_successful_response()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $delegateProphecy->handle($requestProphecy->reveal())
            ->willReturn($responseProphecy->reveal());

        $middleware = new ProblemDetailsMiddleware($loggerProphecy->reveal());
        $response = $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function it_correctly_handles_an_exception_thrown_by_its_delegate()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $delegateProphecy = $this->prophesize(DelegateInterface::class);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $exception = new NotFoundException('Exception message');

        $delegateProphecy->handle($requestProphecy->reveal())
            ->willThrow($exception);

        $middleware = new ProblemDetailsMiddleware($loggerProphecy->reveal());

        /** @var JsonResponse $response */
        $response = $middleware->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $data = $response->getPayload();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Exception message', $data['details']);
    }
}
