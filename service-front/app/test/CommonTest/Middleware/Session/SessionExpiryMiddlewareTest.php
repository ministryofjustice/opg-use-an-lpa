<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionExpiryMiddleware;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionExpiryMiddlewareTest extends TestCase
{
    private MockObject|RequestHandlerInterface $handler;
    private MockObject|ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->handler = $this->createStub(RequestHandlerInterface::class);

        parent::setUp();
    }

    /** @test */
    public function it_correctly_processes_a_non_expired_session(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with(SessionExpiryMiddleware::SESSION_TIME_KEY)
            ->willReturn(time());

        $routeResult = $this->createStub(RouteResult::class);
        $routeResult->method('getMatchedRouteName')
            ->willReturn('home');

        $this->request->method('getAttribute')
            ->withConsecutive(
                [SessionMiddleware::SESSION_ATTRIBUTE],
                [RouteResult::class],
            )
            ->willReturnOnConsecutiveCalls(
                $session,
                $routeResult,
            );

        $sut = new SessionExpiryMiddleware(300);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function it_marks_a_session_as_expired(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with(SessionExpiryMiddleware::SESSION_TIME_KEY)
            ->willReturn(time() - 301);
        $session->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [SessionExpiryMiddleware::SESSION_EXPIRED_KEY, true],
                [SessionExpiryMiddleware::SESSION_TIME_KEY, time()]
            );

        $routeResult = $this->createStub(RouteResult::class);
        $routeResult->method('getMatchedRouteName')
            ->willReturn('home');

        $this->request->method('getAttribute')
            ->withConsecutive(
                [SessionMiddleware::SESSION_ATTRIBUTE],
                [RouteResult::class],
            )
            ->willReturnOnConsecutiveCalls(
                $session,
                $routeResult,
            );

        $sut = new SessionExpiryMiddleware(300);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function it_does_not_increment_session_time_for_javascript_calls(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with(SessionExpiryMiddleware::SESSION_TIME_KEY)
            ->willReturn(time() - 301);
        $session->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [SessionExpiryMiddleware::SESSION_EXPIRED_KEY, true],
                [SessionExpiryMiddleware::SESSION_TIME_KEY, time() - 301]
            );

        $routeResult = $this->createStub(RouteResult::class);
        $routeResult->method('getMatchedRouteName')
            ->willReturn('session-check');

        $this->request->method('getAttribute')
            ->withConsecutive(
                [SessionMiddleware::SESSION_ATTRIBUTE],
                [RouteResult::class],
            )
            ->willReturnOnConsecutiveCalls(
                $session,
                $routeResult,
            );

        $sut = new SessionExpiryMiddleware(300);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
