<?php

declare(strict_types=1);

namespace ActorTest;

use Actor\Form\PasswordReset;
use Actor\Handler\PasswordResetPageHandler;
use Common\Exception\ApiException;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class PasswordResetPageHandlerTest extends TestCase
{
    const CSRF_CODE = '1234';

    /**
     * @var TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var UserService
     */
    private $userServiceProphecy;

    /**
     * @var EmailClient
     */
    private $emailClientProphecy;

    /**
     * @var ServerUrlHelper
     */
    private $serverUrlHelperProphecy;

    /**
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->userServiceProphecy = $this->prophesize(UserService::class);
        $this->emailClientProphecy = $this->prophesize(EmailClient::class);
        $this->serverUrlHelperProphecy = $this->prophesize(ServerUrlHelper::class);

        $this->templateRendererProphecy->render('actor::password-reset', new CallbackToken(function($options) {
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

    /** @test */
    public function a_get_request_returns_a_html_response()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        //  Set up the handler
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function an_invalid_email_submission_returns_a_html_response()
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
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function mismatched_emails_return_html_response()
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
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

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
                'email_confirm' => ''
            ]);

        //  Set up the handler
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function a_valid_form_submission_returns_a_html_response()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'email_confirm' => 'a@b.com'
            ]);

        $this->userServiceProphecy->requestPasswordReset('a@b.com')
            ->willReturn('passwordResetAABBCCDDEE');

        $this->urlHelperProphecy->generate('password-reset-token', [ 'token' => 'passwordResetAABBCCDDEE' ])
            ->willReturn('/password-reset/passwordResetAABBCCDDEE');

        $this->serverUrlHelperProphecy->generate('/password-reset/passwordResetAABBCCDDEE')
            ->willReturn('http://localhost:9002/password-reset/passwordResetAABBCCDDEE');

        $this->templateRendererProphecy->render('actor::password-reset-done', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('email', $options);
            $this->assertIsString($options['email']);

            return true;
        }))
            ->willReturn('');

        //  Set up the handler
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function when_actor_not_found_a_html_response_is_given_email_not_sent()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'email' => 'a@b.com',
                'email_confirm' => 'a@b.com'
            ]);

        $this->userServiceProphecy->requestPasswordReset('a@b.com')
            ->willThrow(new ApiException('User not found'));

        $this->templateRendererProphecy->render('actor::password-reset-done', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('email', $options);
            $this->assertIsString($options['email']);

            return true;
        }))
            ->willReturn('');

        //  Set up the handler
        $handler = new PasswordResetPageHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal(),
            $this->emailClientProphecy->reveal(),
            $this->serverUrlHelperProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}