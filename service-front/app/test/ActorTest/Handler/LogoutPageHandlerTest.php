<?php

declare(strict_types=1);

namespace ActorTest;

use Actor\Handler\LogoutPageHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionMiddleware;
use Zend\Expressive\Template\TemplateRendererInterface;

class LogoutPageHandlerTest extends TestCase
{
    private $urlHelperProphecy;
    private $requestProphecy;
    private $sessionProphecy;

    public function setUp()
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->urlHelperProphecy->generate(Argument::any(), Argument::any(), Argument::any())
            ->willReturn('http:://localhost/test');

        $this->sessionProphecy = $this->prophesize(SessionInterface::class);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

    }

    /** @test */
    public function returns_redirect_response_when_after_clearing_session_user()
    {
        $this->sessionProphecy->unset(UserInterface::class)->shouldBeCalled();
        $this->sessionProphecy->regenerate()->shouldBeCalled();

        $this->requestProphecy->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($this->sessionProphecy->reveal());

        //  Set up the handler
        $handler = new LogoutPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}