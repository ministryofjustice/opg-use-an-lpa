<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionExpiredAttributeWhitelistMiddleware;
use Common\Service\Session\EncryptedCookiePersistence;
use DateTime;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class SessionExpiredAttributeWhitelistMiddlewareTest extends TestCase
{
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

        $sem = new SessionExpiredAttributeWhitelistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_correctly_handles_an_non_expired_session(): void
    {
        $sessionProphecy = $this->prophesize(Session::class);
        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(null);

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

        $sem = new SessionExpiredAttributeWhitelistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_strips_session_values_with_correct_whitelisting(): void
    {
        $sessionData = [
            'string' => 'one',
            'bool' => true,
            'DateTime' => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY => time(),
            EncryptedCookiePersistence::SESSION_EXPIRED_KEY => true
        ];

        $sessionProphecy = $this->prophesize(Session::class);
        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(true);
        $sessionProphecy
            ->toArray()
            ->shouldBeCalled()
            ->willReturn($sessionData);
        $sessionProphecy
            ->unset(Argument::type('string'))
            ->shouldBeCalledTimes(4); // SESSION_TIME_KEY is whitelisted

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

        $sem = new SessionExpiredAttributeWhitelistMiddleware(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $sem->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
