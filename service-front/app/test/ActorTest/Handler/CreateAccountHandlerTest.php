<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\CreateAccount;
use Actor\Handler\CreateAccountHandler;
use Common\Exception\ApiException;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;

class CreateAccountHandlerTest extends TestCase
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
            $this->assertInstanceOf(CreateAccount::class, $options['form']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedCreateAccount()
    {
        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willReturn([
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $this->urlHelperProphecy->generate('activate-account', [
                'token' => 'activate1234567890',
            ])
            ->willReturn('/activate-account/activate1234567890');
        $this->urlHelperProphecy->generate('create-account-success', [], [
                'email' => 'a@b.com',
            ])
            ->willReturn('/create-account-success?email=a@b.com');

        $this->serverUrlHelperProphecy->generate('/activate-account/activate1234567890')
            ->willReturn('http://localhost/activate-account/activate1234567890');

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('POST');
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'                => self::CSRF_CODE,
                'email'                 => 'a@b.com',
                'show_hide_password'    => 'P@55word',
                'terms'                 => '1',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedCreateAccountExists()
    {
        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willThrow(new ApiException('Conflict', StatusCodeInterface::STATUS_CONFLICT));

        $this->urlHelperProphecy->generate('create-account-success', [], [
                'email' => 'a@b.com',
            ])
            ->willReturn('/create-account-success?email=a@b.com');

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldBeCalled();

        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('emailAddress', $options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('POST');
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'                => self::CSRF_CODE,
                'email'                 => 'a@b.com',
                'show_hide_password'    => 'P@55word',
                'terms'                 => '1',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedCreateAccountException()
    {
        $this->userServiceProphecy->create('a@b.com', 'P@55word')
            ->willThrow(new ApiException('Internal Server Error', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();
        $this->emailClientProphecy->sendAlreadyRegisteredEmail('a@b.com')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('POST');
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf'                => self::CSRF_CODE,
                'email'                 => 'a@b.com',
                'show_hide_password'    => 'P@55word',
                'terms'                 => '1',
            ]);

        $this->expectException(ApiException::class);

        $handler->handle($this->requestProphecy->reveal());
    }
}
