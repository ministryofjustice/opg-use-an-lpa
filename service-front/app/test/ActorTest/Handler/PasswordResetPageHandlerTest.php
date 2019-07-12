<?php

declare(strict_types=1);

namespace ActorTest;

use Actor\Form\PasswordReset;
use Actor\Handler\PasswordResetPageHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class PasswordResetPageHandlerTest extends TestCase
{
    const CSRF_CODE="1234";

    private $rendererProphecy;
    private $urlHelperProphecy;
    private $userServiceProphecy;
    private $requestProphecy;

    public function setUp()
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->rendererProphecy->render('actor::password-reset', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(PasswordReset::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

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
        $handler = new PasswordResetPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

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
                'email_confirm' => 'bademail'
            ]);

        //  Set up the handler
        $handler = new PasswordResetPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testMismatchedEmail()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'email_confirm' => 'a@c.com'
            ]);

        //  Set up the handler
        $handler = new PasswordResetPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

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
                'email_confirm' => ''
            ]);

        //  Set up the handler
        $handler = new PasswordResetPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testValidSubmission()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'email_confirm' => 'a@b.com'
            ]);

        $this->rendererProphecy->render('actor::password-reset-done', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('email', $options);
            $this->assertIsString($options['email']);

            return true;
        }))
            ->willReturn('');

        //  Set up the handler
        $handler = new PasswordResetPageHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}