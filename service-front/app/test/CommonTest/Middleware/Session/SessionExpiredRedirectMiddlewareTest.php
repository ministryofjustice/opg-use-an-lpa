<?php

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionExpiredRedirectMiddleware;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionExpiredRedirectMiddlewareTest extends TestCase
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

        $request = new SessionExpiredRedirectMiddleware(
            $this->prophesize(ServerUrlHelper::class)->reveal()
        );

        $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());
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

        $request = new SessionExpiredRedirectMiddleware(
            $this->prophesize(ServerUrlHelper::class)->reveal()
        );

        $result = $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    /** @test */
    public function it_correctly_redirects_when_session_expires(): void
    {
        $uri = 'https://localhost:9002/session-expired';

        $sessionProphecy = $this->prophesize(Session::class);
        $helperProphecy = $this->prophesize(ServerUrlHelper::class);
        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(true);

        $sessionProphecy
            ->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled();

        $helperProphecy
            ->generate('/session-expired')
            ->willReturn($uri);

        $request = new SessionExpiredRedirectMiddleware(
            $helperProphecy->reveal()
        );

        $redirect = $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertEquals($uri, $redirect->getHeader('location')[0]);
    }
}
