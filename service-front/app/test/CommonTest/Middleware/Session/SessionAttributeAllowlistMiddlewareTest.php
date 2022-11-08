<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionAttributeAllowlistMiddleware;
use Common\Service\Session\EncryptedCookiePersistence;
use DateTime;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class SessionAttributeAllowlistMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_correctly_handles_request_with_no_session(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn(null);

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $sem = new SessionAttributeAllowlistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_correctly_handles_an_non_expired_session(): void
    {
        $sessionProphecy = $this->prophesize(Session::class);
        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(false);
        $sessionProphecy
            ->has(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED)
            ->shouldBeCalled()
            ->willReturn(false);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $sem = new SessionAttributeAllowlistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_strips_session_values_that_have_not_been_allowed(): void
    {
        $sessionData = [
            'string'                                        => 'one',
            'bool'                                          => true,
            'DateTime'                                      => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY    => time() - 300, // session expired 5 minutes ago
            EncryptedCookiePersistence::SESSION_EXPIRED_KEY => true,
        ];

        $sessionProphecy = $this->prophesize(Session::class);
        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(true);
        $sessionProphecy
            ->has(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED)
            ->shouldBeCalled()
            ->willReturn(false);
        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->shouldBeCalled()
            ->willReturn($sessionData[EncryptedCookiePersistence::SESSION_TIME_KEY]);
        $sessionProphecy
            ->toArray()
            ->shouldBeCalled()
            ->willReturn($sessionData);
        $sessionProphecy
            ->unset(Argument::type('string'))
            ->shouldBeCalledTimes(4); // SESSION_TIME_KEY is allowed

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $sem = new SessionAttributeAllowlistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_strips_session_values_when_a_logout_has_been_flagged(): void
    {
        $sessionData = [
            'string'                                     => 'one',
            'bool'                                       => true,
            'DateTime'                                   => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY => time() + 300,
        ];

        $sessionProphecy = $this->prophesize(Session::class);
        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(false);
        $sessionProphecy
            ->has(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED)
            ->shouldBeCalled()
            ->willReturn(true);
        $sessionProphecy
            ->toArray()
            ->shouldBeCalled()
            ->willReturn($sessionData);
        $sessionProphecy
            ->unset(Argument::type('string'))
            ->shouldBeCalledTimes(4); // SESSION_TIME_KEY is allowed

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $sem = new SessionAttributeAllowlistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
