<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use Common\Middleware\Authentication\CredentialAuthenticationMiddleware;
use Common\Middleware\Session\SessionAttributeAllowlistMiddleware;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CredentialAuthenticationMiddlewareTest extends TestCase
{
    private MockObject|AuthenticationInterface $auth;
    private MockObject|RequestHandlerInterface $handler;
    private MockObject|UrlHelper $helper;
    private MockObject|ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->auth   = $this->createStub(AuthenticationInterface::class);
        $this->helper = $this->createStub(UrlHelper::class);

        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->handler = $this->createStub(RequestHandlerInterface::class);

        parent::setUp();
    }

    /** @test */
    public function it_requires_a_session(): void
    {
        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn(null);

        $this->handler->expects($this->never())
            ->method('handle');

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $this->expectException(MissingSessionContainerException::class);
        $sut->process($this->request, $this->handler);
    }

    /** @test */
    public function it_redirects_upon_an_expired_session(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('has')
            ->with(SessionExpiryMiddleware::SESSION_EXPIRED_KEY)
            ->willReturn(true);

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->handler->expects($this->never())
            ->method('handle');

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /** @test */
    public function it_returns_an_unauthorised_response_if_auth_fails(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('has')
            ->willReturn(false);

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->auth->expects($this->once())
            ->method('authenticate')
            ->with($this->request)
            ->willReturn(null);

        $this->handler->expects($this->never())
            ->method('handle');

        $this->auth->expects($this->once())
            ->method('unauthorizedResponse')
            ->willReturn($this->createStub(RedirectResponse::class));

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /** @test */
    public function it_continues_to_the_handler_if_auth_is_successful(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('has')
            ->withConsecutive(
                [SessionExpiryMiddleware::SESSION_EXPIRED_KEY],
                [UserInterface::class],
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
            );

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->auth->expects($this->once())
            ->method('authenticate')
            ->willReturn($this->createStub(UserInterface::class));

        $this->request->method('withAttribute')
            ->willReturnSelf();

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function a_handler_can_request_a_logout(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('has')
            ->willReturn(false);
        $session->expects($this->once())
            ->method('set')
            ->with(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED, true);

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->auth->method('authenticate')
            ->willReturn($this->createStub(UserInterface::class));

        $this->request->method('withAttribute')
            ->willReturnSelf();

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
