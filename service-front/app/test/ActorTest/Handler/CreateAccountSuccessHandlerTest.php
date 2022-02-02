<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\CreateAccountSuccessHandler;
use Common\Exception\ApiException;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;

class CreateAccountSuccessHandlerTest extends TestCase
{
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

    public function setUp(): void
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->userServiceProphecy = $this->prophesize(UserService::class);
        $this->emailClientProphecy = $this->prophesize(EmailClient::class);
        $this->serverUrlHelperProphecy = $this->prophesize(ServerUrlHelper::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
    }

    public function testSimplePageGet()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new CreateAccountSuccessHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'email' => 'a@b.com'
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSimplePageGetNoEmail()
    {
        $this->urlHelperProphecy->generate('create-account', [], [])
            ->willReturn('/create-account');

        //  Set up the handler
        $handler = new CreateAccountSuccessHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getQueryParams()
            ->willReturn([]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedResendActivationEmail()
    {
        $this->userServiceProphecy->getByEmail('a@b.com')
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

        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('email', $options);
            $this->assertEquals('a@b.com', $options['email']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new CreateAccountSuccessHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'email'  => 'a@b.com',
                'resend' => 'true',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedResendActivationEmailNoUser()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        $this->userServiceProphecy->getByEmail('a@b.com')
            ->willThrow(new ApiException('Not Found', StatusCodeInterface::STATUS_NOT_FOUND));

        $this->urlHelperProphecy->generate('create-account-success', [], [
                'emailAddress' => 'a@b.com',
            ])
            ->willReturn('/create-account');

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountSuccessHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'email'  => 'a@b.com',
                'resend' => 'true',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmittedResendActivationEmailNoToken()
    {
        $this->templateRendererProphecy->render('actor::create-account-success', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertEquals('a@b.com', $options['emailAddress']);

            return true;
        }))->willReturn('');

        $this->userServiceProphecy->getByEmail('a@b.com')
            ->willReturn([
                'Email'           => 'a@b.com',
                //  No ActivationToken
            ]);

        $this->urlHelperProphecy->generate('create-account-success', [], [
                'emailAddress' => 'a@b.com',
            ])
            ->willReturn('/create-account');

        $this->emailClientProphecy->sendAccountActivationEmail('a@b.com', 'http://localhost/activate-account/activate1234567890')
            ->shouldNotBeCalled();

        //  Set up the handler
        $handler = new CreateAccountSuccessHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->userServiceProphecy->reveal(), $this->emailClientProphecy->reveal(), $this->serverUrlHelperProphecy->reveal());

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'email'  => 'a@b.com',
                'resend' => 'true',
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
