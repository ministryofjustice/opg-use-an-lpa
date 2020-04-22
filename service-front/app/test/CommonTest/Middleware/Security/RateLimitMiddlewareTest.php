<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Exception\RateLimitExceededException;
use Common\Middleware\Security\RateLimitMiddleware;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Security\RateLimit\KeyedRateLimitService;
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

    /** @test */
    public function an_identified_user_is_checked_for_limit_transgressions_and_no_limiters_available()
    {
        $factoryProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $factoryProphecy
            ->all()
            ->shouldBeCalled()
            ->willReturn([]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn('an-identity-hash');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rlm = new RateLimitMiddleware($factoryProphecy->reveal());

        $response = $rlm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function an_identified_user_is_checked_for_limit_transgressions_with_one_limiter()
    {
        $limiterProphecy = $this->prophesize(KeyedRateLimitService::class);
        $limiterProphecy
            ->isLimited('an-identity-hash')
            ->shouldBeCalled()
            ->willReturn(false);

        $factoryProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $factoryProphecy
            ->all()
            ->shouldBeCalled()
            ->willReturn([$limiterProphecy->reveal()]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn('an-identity-hash');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rlm = new RateLimitMiddleware($factoryProphecy->reveal());

        $response = $rlm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function an_identified_user_is_checked_for_limit_transgressions_with_multiple_limiters()
    {
        $limiterOneProphecy = $this->prophesize(KeyedRateLimitService::class);
        $limiterOneProphecy
            ->isLimited('an-identity-hash')
            ->shouldBeCalled()
            ->willReturn(false);

        $limiterTwoProphecy = $this->prophesize(KeyedRateLimitService::class);
        $limiterTwoProphecy
            ->isLimited('an-identity-hash')
            ->shouldBeCalled()
            ->willReturn(false);

        $factoryProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $factoryProphecy
            ->all()
            ->shouldBeCalled()
            ->willReturn([$limiterOneProphecy->reveal(), $limiterTwoProphecy->reveal()]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn('an-identity-hash');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rlm = new RateLimitMiddleware($factoryProphecy->reveal());

        $response = $rlm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function an_identified_user_is_limited()
    {
        $limiterProphecy = $this->prophesize(KeyedRateLimitService::class);
        $limiterProphecy
            ->isLimited('an-identity-hash')
            ->shouldBeCalled()
            ->willReturn(true);
        $limiterProphecy
            ->getName()
            ->willReturn('limiter');

        $factoryProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $factoryProphecy
            ->all()
            ->shouldBeCalled()
            ->willReturn([$limiterProphecy->reveal()]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn('an-identity-hash');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);

        $rlm = new RateLimitMiddleware($factoryProphecy->reveal());

        $this->expectException(RateLimitExceededException::class);
        $response = $rlm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
