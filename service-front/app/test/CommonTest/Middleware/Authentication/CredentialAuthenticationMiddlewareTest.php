<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use Common\Middleware\Authentication\CredentialAuthenticationMiddleware;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
        $this->auth   = $this->createMock(AuthenticationInterface::class);
        $this->helper = $this->createMock(UrlHelper::class);

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        parent::setUp();
    }

    /** @test */
    public function it_requires_a_session()
    {
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn(null);

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $this->expectException(MissingSessionContainerException::class);
        $sut->process($this->request, $this->handler);
    }

    /** @test */
    public function it_redirects_upon_an_expired_session()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('has')
            ->with(SessionExpiryMiddleware::SESSION_EXPIRED_KEY)
            ->willReturn(true);

        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);

        $sut = new CredentialAuthenticationMiddleware($this->auth, $this->helper);

        $response = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
