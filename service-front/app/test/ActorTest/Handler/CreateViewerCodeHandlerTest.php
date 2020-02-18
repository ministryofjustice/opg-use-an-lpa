<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\CreateShareCode;
use Actor\Handler\CreateViewerCodeHandler;
use ArrayObject;
use Common\Entity\User;
use Common\Exception\InvalidRequestException;
use Common\Service\Lpa\Factory\Sirius as LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class CreateViewerCodeHandlerTest extends TestCase
{
    const CSRF_CODE = '123456';
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const LPA_ID = '01234567-01234-01234-01234-012345678901';
    const ORG_NAME = 'HSBC';

    /**
     * @var TemplateRendererInterface
     */
    private $rendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var AuthenticationInterface
     */
    private $authenticatorProphecy;

    /**
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $sessionProphecy;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $userProphecy;

    /**
     * @var ObjectProphecy|LpaService
     */
    private $lpaServiceProphecy;

    /**
     * @var ObjectProphecy|ViewerCodeService
     */
    private $viewerCodeServiceProphecy;

    public function setUp(): void
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->sessionProphecy = $this->prophesize(SessionInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());

        $this->rendererProphecy->render('actor::lpa-create-viewercode', Argument::that(function ($options) {
            $this->assertIsArray($options);

            $this->assertArrayHasKey('lpa', $options);
            $this->assertArrayHasKey('user', $options);
            $this->assertArrayHasKey('actorToken', $options);

            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(CreateShareCode::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $this->userProphecy = $this->prophesize(UserInterface::class);
        $this->userProphecy->getIdentity()->willReturn(self::IDENTITY_TOKEN);
    }

    /** @test */
    public function it_returns_a_html_response_when_accessed_via_get()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $this->urlHelperProphecy->generate('lpa.check', [], [])
            ->willReturn('/lpa/check');

        $viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new CreateViewerCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $lpaServiceProphecy->reveal(),
            $viewerCodeServiceProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function it_raises_an_error_if_the_lpa_isnt_specified_on_a_get_request()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        $this->requestProphecy->getQueryParams()
            ->willReturn([]);

        $viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new CreateViewerCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $lpaServiceProphecy->reveal(),
            $viewerCodeServiceProphecy->reveal()
        );

        $this->expectException(InvalidRequestException::class);
        $response = $handler->handle($this->requestProphecy->reveal());
    }

    /** @test */
    public function it_shows_error_when_invalid_form_is_submitted()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';
        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE
            ]);

        $handler = new CreateViewerCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->viewerCodeServiceProphecy->createShareCode(
            self::IDENTITY_TOKEN,
            self::LPA_ID,
            '')->willReturn('');

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);

    }

    /** @test */
    public function it_shows_viewer_code_when_post_occurs()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';
        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE
            ]);
        $this->urlHelperProphecy->generate(Argument::type('string'))->willReturn('http://localhost');

        $handler = new CreateViewerCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->viewerCodeServiceProphecy->createShareCode(
            self::IDENTITY_TOKEN,
            self::LPA_ID,
            self::ORG_NAME)->willReturn('viewer-lpa-code');

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);

    }

    /** @test */
    public function it_navigates_to_show_viewer_code_when_post_occurs()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';
        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE
            ]);
        $this->urlHelperProphecy->generate(Argument::type('string'))->willReturn('http://localhost');

        $handler = new CreateViewerCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $viewerCode = new ArrayObject(['code' => 'FJ6LJ6VQKHF8', 'expires' => '2019-12-28T23:59:59+00:00', 'organisation' => 'HSBC']);

        $lpa = (new LpaFactory())->createLpaFromData([
            'uId' => '700000000047',
            'donor' => [
                'uId' => '700000000082',
                'dob' => '1975-01-01'
            ],
            'attorneys' => [
                [
                    'uId' => '700000000023',
                    'dob' => '1980-01-01'
                ]
            ]
        ]);

        $this->viewerCodeServiceProphecy->createShareCode(
            self::IDENTITY_TOKEN,
            self::LPA_ID,
            self::ORG_NAME)->willReturn($viewerCode);

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $this->rendererProphecy
            ->render('actor::lpa-show-viewercode', [
            'user'         => self::IDENTITY_TOKEN,
            'actorToken'   => self::LPA_ID,
            'code'         => $viewerCode['code'],
            'expires'      => $viewerCode['expires'],
            'organisation' => $viewerCode['organisation'],
            'lpa'          => $lpa
        ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);

    }

    private function authenticateRequest(string $actorId)
    {
        $identity = $this->prophesize(User::class);
        $identity->getIdentity()->willReturn($actorId);
        $this->authenticatorProphecy->authenticate($this->requestProphecy->reveal())->willReturn($identity->reveal());
    }
}
