<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use PHPUnit\Framework\Attributes\Test;
use Common\Middleware\Authentication\ForcedPasswordResetMiddleware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForcedPasswordResetMiddlewareTest extends TestCase
{
    private AuthenticationInterface|MockObject $authenticator;
    private RequestHandlerInterface|MockObject $handler;
    private ServerRequestInterface|MockObject $request;
    private ResponseInterface|Stub $response;
    private TemplateRendererInterface|MockObject $templateRenderer;
    private UrlHelper|MockObject $urlHelper;
    private UserInterface|MockObject $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticator    = $this->createMock(AuthenticationInterface::class);
        $this->urlHelper        = $this->createMock(UrlHelper::class);

        $this->user = $this->createMock(UserInterface::class);

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        $this->response = $this->createStub(ResponseInterface::class);
    }

    #[Test]
    public function it_continues_the_pipeline_if_not_needing_reset(): void
    {
        $this->authenticator->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(ServerRequestInterface::class))
            ->willReturn($this->user);

        $matcher = $this->exactly(2);
        $this->user->expects($matcher)
            ->method('getDetail')
            ->willReturnCallback(function (string $param) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('Email', $param),
                    2 => $this->assertEquals('NeedsReset', $param),
                };
            });

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->response);

        $sut = new ForcedPasswordResetMiddleware($this->templateRenderer, $this->authenticator, $this->urlHelper);

        $result = $sut->process($this->request, $this->handler);

        $this->assertEquals($this->response, $result);
    }

    #[Test]
    public function it_renders_a_page_if_user_password_needs_reset(): void
    {
        $this->authenticator->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(ServerRequestInterface::class))
            ->willReturn($this->user);

        $matcher = $this->exactly(2);
        $this->user->expects($matcher)
            ->method('getDetail')
            ->willReturnCallback(function (string $param) use ($matcher) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals('Email', $param);
                        return 'test@example.com';

                    case 2:
                        $this->assertEquals('NeedsReset', $param);
                        return true;
                }
            });

        $csrfGuard = $this->createStub(CsrfGuardInterface::class);

        $this->request->method('getAttribute')
            ->with(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfGuard);

        $sut = new ForcedPasswordResetMiddleware($this->templateRenderer, $this->authenticator, $this->urlHelper);

        $result = $sut->process($this->request, $this->handler);

        $this->assertInstanceOf(HtmlResponse::class, $result);
    }
}
