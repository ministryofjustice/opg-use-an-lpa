<?php

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionExpiredRedirectMiddleware;
use Common\Service\Session\EncryptedCookiePersistence;
use DateTime;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
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
            $this->prophesize(UrlHelper::class)->reveal()
        );

        $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_correctly_handles_an_non_expired_session(): void
    {
        $sessionData = [
            'string' => 'one',
            'bool' => true,
            'DateTime' => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY => time() - 300, // session expired 5 minutes ago
            EncryptedCookiePersistence::SESSION_EXPIRED_KEY => false
        ];

        $sessionProphecy = $this->prophesize(Session::class);
        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());
        
        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(false);

        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $request = new SessionExpiredRedirectMiddleware(
            $this->prophesize(UrlHelper::class)->reveal());

        $result = $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    /** @test */
    public function it_correctly_redirects_to_session_expired_page_when_session_expires(): void
    {
        $sessionData = [
            'string' => 'one',
            'bool' => true,
            'DateTime' => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY => time() - 300, // session expired 5 minutes ago
            EncryptedCookiePersistence::SESSION_EXPIRED_KEY => true
        ];

        $uri = 'https://localhost:9002/session-expired';
        $uriHome = 'https://localhost:9002/home';

        $sessionProphecy = $this->prophesize(Session::class);
        $helperProphecy = $this->prophesize(UrlHelper::class);
        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->shouldBeCalled()
            ->willReturn($sessionData[EncryptedCookiePersistence::SESSION_TIME_KEY]);

        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(true);

        $sessionProphecy
            ->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled();

        $helperProphecy
            ->generate('session-expired')
            ->willReturn($uri);

        $helperProphecy
            ->generate('home')
            ->willReturn($uri);

        $request = new SessionExpiredRedirectMiddleware(
            $helperProphecy->reveal()
        );

        $redirect = $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertEquals($uri, $redirect->getHeader('location')[0]);
    }

    /** @test */
    public function it_correctly_redirects_to_home_page_when_session_expires(): void
    {
        $sessionData = [
            'string' => 'one',
            'bool' => true,
            'DateTime' => new DateTime(),
            EncryptedCookiePersistence::SESSION_TIME_KEY => time() - 24*60*60, // session expired 1 day before current time ago
            EncryptedCookiePersistence::SESSION_EXPIRED_KEY => true
        ];

        $uri = 'https://localhost:9002/home';

        $sessionProphecy = $this->prophesize(Session::class);
        $helperProphecy = $this->prophesize(UrlHelper::class);
        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $sessionProphecy
            ->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->shouldBeCalled()
            ->willReturn($sessionData[EncryptedCookiePersistence::SESSION_TIME_KEY]);

        $sessionProphecy
            ->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled()
            ->willReturn(true);

        $sessionProphecy
            ->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)
            ->shouldBeCalled();

        $helperProphecy
            ->generate('home')
            ->willReturn($uri);

        $request = new SessionExpiredRedirectMiddleware(
            $helperProphecy->reveal()
        );

        $redirect = $request->process($requestProphecy->reveal(), $delegateProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertEquals($uri, $redirect->getHeader('location')[0]);
    }
}
