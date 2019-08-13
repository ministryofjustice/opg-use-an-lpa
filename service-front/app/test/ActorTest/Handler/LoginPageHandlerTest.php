<?php

declare(strict_types=1);

namespace ActorTest;

use Actor\Handler\LoginPageHandler;
use Common\Entity\User;
use Common\Service\User\UserService;
use Grpc\Server;
use PHPUnit\Framework\TestCase;
use Actor\Form\Login;
use Prophecy\Argument;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
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
        $this->urlHelperProphecy->generate(Argument::any(), Argument::any(), Argument::any())
            ->willReturn('http:://localhost/test');

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

    /** @test */
    public function get_request_returns_html_response()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function invalid_email_returns_html_response()
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

    /** @test */
    public function empty_fields_return_html_response()
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

    /** @test */
    public function valid_fields_return_html_response_when_credentials_bad()
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

    /** @test */
    public function returns_redirect_response_when_credentials_good()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'password' => '1234'
            ]);

        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn(new User('test', [], []));

        //  Set up the handler
        $handler = new LoginPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}