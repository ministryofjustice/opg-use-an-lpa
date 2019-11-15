<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\LpaDashboardHandler;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Common\Service\Lpa\ViewerCodeService;
use Zend\Diactoros\Response\RedirectResponse;
use ArrayObject;

class LpaDashboardHandlerTest extends TestCase
{
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const USER_LPA_ACTOR_TOKEN = '98765432-12345-54321-12345-9876543210';
    const ACTOR_ID = 10;

    /**
     * @var TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var LpaService
     */
    private $lpaServiceProphecy;

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
    private $userProphecy;

    /**
     * @var ObjectProphecy|ViewerCodeService
     */
    private $viewerCodeServiceProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);
        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->templateRendererProphecy->render('actor::lpa-dashboard', Argument::that(function ($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('user', $options);
            $this->assertArrayHasKey('lpas', $options);
            return true;
        }))
            ->willReturn('');

        $this->userProphecy = $this->prophesize(UserInterface::class);
        $this->userProphecy->getIdentity()->willReturn(self::IDENTITY_TOKEN);
    }

    /** @test */
    public function dashboard_is_displayed_with_lpas_added()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new LpaDashboardHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $lpas = new ArrayObject([
            [
                'lpa' => [],
                'user-lpa-actor-token' => self::USER_LPA_ACTOR_TOKEN
            ],
        ], ArrayObject::ARRAY_AS_PROPS);

        $shareCodes = new ArrayObject(['activeCodeCount' => 1], ArrayObject::ARRAY_AS_PROPS);

        $this->lpaServiceProphecy
            ->getLpas(self::IDENTITY_TOKEN)
            ->willReturn($lpas);

        $this->viewerCodeServiceProphecy
            ->getShareCodes(self::IDENTITY_TOKEN, self::USER_LPA_ACTOR_TOKEN, true)
            ->willReturn($shareCodes);

        $this->templateRendererProphecy
            ->render('actor:lpa-dashboard', [
                'user' => self::IDENTITY_TOKEN,
                'lpa' => $lpas,
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function user_is_redirected_to_add_page_when_no_lpas_added()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new LpaDashboardHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $lpas = new ArrayObject([]);

        $this->lpaServiceProphecy
            ->getLpas(self::IDENTITY_TOKEN)
            ->willReturn($lpas);

        $this->urlHelperProphecy->generate('lpa.add');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

}