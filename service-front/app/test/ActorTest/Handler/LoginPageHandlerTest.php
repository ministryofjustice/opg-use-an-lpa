<?php

declare(strict_types=1);

namespace ActorTest;

use Actor\Handler\LoginPageHandler;
use Common\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Actor\Form\Login;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class LoginPageHandlerTest extends TestCase
{
    const CSRF_CODE="1234";

    private $rendererProphecy;
    private $urlHelperProphecy;
    private $authenticatorProphecy;
    private $requestProphecy;

    public function setUp()
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->rendererProphecy->render('actor::login', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(Login::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());
    }

    public function testGetReturnsHtmlResponse()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidEmail()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'bademail',
                'password' => '1234'
            ]);

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testEmptyFields()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => '',
                'email' => '',
                'password' => ''
            ]);

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testValidFields()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'password' => '1234'
            ]);

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}