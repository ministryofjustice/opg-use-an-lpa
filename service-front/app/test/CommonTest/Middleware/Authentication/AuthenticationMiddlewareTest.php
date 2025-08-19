<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use Common\Middleware\Authentication\AuthenticationMiddleware;
use Common\Middleware\Session\SessionAttributeAllowlistMiddleware;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    private MockObject&RequestHandlerInterface $handler;
    private Stub&UrlHelper $helper;
    private MockObject&ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(UrlHelper::class);

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        parent::setUp();
    }

    #[Test]
    public function it_requires_a_session(): void
    {
        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn(null);

        $this->handler->expects($this->never())
            ->method('handle');

        $sut = new AuthenticationMiddleware($this->helper);

        $this->expectException(MissingSessionContainerException::class);
        $sut->process($this->request, $this->handler);
    }

    #[Test]
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

        $sut = new AuthenticationMiddleware($this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function it_returns_an_unauthorised_response_if_auth_fails(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('has')
            ->willReturn(false);

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->handler->expects($this->never())
            ->method('handle');

        $sut = new AuthenticationMiddleware($this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function it_continues_to_the_handler_if_auth_is_successful(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('get')
            ->with(UserInterface::class)
            ->willReturn(
                [
                    'username' => 'test',
                    'roles'    => [],
                    'details'  => [
                        'Email' => 'test@test.com',
                    ],
                ]
            );

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->request->method('withAttribute')
            ->willReturnSelf();

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->createStub(ResponseInterface::class));

        $sut = new AuthenticationMiddleware($this->helper);
        $sut->process($this->request, $this->handler);
    }

    #[Test]
    public function a_handler_can_request_a_logout(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')
            ->willReturn(false);
        $session->expects($this->once())
            ->method('set')
            ->with(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED, true);

        $this->request->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $this->request->method('withAttribute')
            ->willReturnSelf();

        $sut = new AuthenticationMiddleware($this->helper);

        $sut->process($this->request, $this->handler);
    }
}
