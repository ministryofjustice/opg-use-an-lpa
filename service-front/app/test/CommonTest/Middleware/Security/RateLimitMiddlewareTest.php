<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\RateLimitMiddleware;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Security\RateLimitServiceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddlewareTest extends TestCase
{
    /** @test */
    public function it_functions_without_an_identity(): void
    {
        $factoryProphecy = $this->prophesize(RateLimitServiceFactory::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn(null);

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rlm = new RateLimitMiddleware($factoryProphecy->reveal());

        $response = $rlm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
