<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\ConfirmEmail;
use Actor\Form\CreateAccount;
use Actor\Handler\CreateAccountHandler;
use Common\Service\ApiClient\ApiException;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Helper\UrlHelper;

class CreateAccountHandlerTest extends TestCase
{
    const CSRF_CODE = "1234";

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

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);

        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());
    }

    public function testSimplePageGet()
    {
        $this->templateRendererProphecy->render('actor::create-account', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(CreateAccount::class, $options['form']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("GET");

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedCreateAccount()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ConfirmEmail::class, $options['form']);

            $this->assertArrayHasKey('emailAddress', $options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        $this->urlHelperProphecy->generate('activate-account', [
                'token' => 'activate1234567890',
            ])
            ->willReturn('/activate-account/activate1234567890');

        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willReturn([
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getScheme()
            ->willReturn('http');
        $uriProphecy->getAuthority()
            ->willReturn('localhost');

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
                'password'         => 'P@55word',
                'password_confirm' => 'P@55word',
                'terms'            => '1',
            ]);
        $this->requestProphecy->getUri()
            ->willReturn($uriProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedCreateAccountExists()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ConfirmEmail::class, $options['form']);

            $this->assertArrayHasKey('emailAddress', $options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willThrow($this->getMockApiException(StatusCodeInterface::STATUS_CONFLICT));

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
                'password'         => 'P@55word',
                'password_confirm' => 'P@55word',
                'terms'            => '1',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedCreateAccountException()
    {
        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willThrow($this->getMockApiException(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
                'password'         => 'P@55word',
                'password_confirm' => 'P@55word',
                'terms'            => '1',
            ]);

        $this->expectException(ApiException::class);

        $handler->handle($this->requestProphecy->reveal());
    }

    public function testFormSubmittedResendActivationEmail()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ConfirmEmail::class, $options['form']);

            $this->assertArrayHasKey('emailAddress', $options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        $this->urlHelperProphecy->generate('activate-account', [
                'token' => 'activate1234567890',
            ])
            ->willReturn('/activate-account/activate1234567890');

        $this->userServiceProphecy->getByEmail('a@b.com')
            ->willReturn([
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getScheme()
            ->willReturn('http');
        $uriProphecy->getAuthority()
            ->willReturn('localhost');

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
            ]);
        $this->requestProphecy->getUri()
            ->willReturn($uriProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedResendActivationEmailNoUser()
    {
        $this->urlHelperProphecy->generate('create-account', [], [])
            ->willReturn('/create-account');

        $this->userServiceProphecy->getByEmail('a@b.com')
            ->willThrow($this->getMockApiException(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedResendActivationEmailNoToken()
    {
        $this->urlHelperProphecy->generate('create-account', [], [])
            ->willReturn('/create-account');

        $this->userServiceProphecy->getByEmail('a@b.com')
            ->willReturn([
                'Email'           => 'a@b.com',
                //  No ActivationToken
            ]);

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'           => self::CSRF_CODE,
                'email'            => 'a@b.com',
                'email_confirm'    => 'a@b.com',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @param int $statusCode
     * @return ApiException
     */
    private function getMockApiException(int $statusCode) : ApiException
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()
            ->willReturn('{}');

        $apiResponseProphecy = $this->prophesize(ResponseInterface::class);
        $apiResponseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $apiResponseProphecy->getStatusCode()
            ->willReturn($statusCode);

        return new ApiException($apiResponseProphecy->reveal());
    }
}
