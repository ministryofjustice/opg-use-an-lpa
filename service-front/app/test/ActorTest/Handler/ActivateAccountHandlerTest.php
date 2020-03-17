<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Common\Service\User\UserService;
use Actor\Handler\ActivateAccountHandler;
use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;

class ActivateAccountHandlerTest extends TestCase
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
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->userServiceProphecy = $this->prophesize(UserService::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->requestProphecy->getAttribute('token')->willReturn('tok123');
    }

    public function testHandle()
    {
        $handler = new ActivateAccountHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal()
        );

        $this->userServiceProphecy->activate('tok123')->willReturn(true);

        $this->templateRendererProphecy->render('actor::activate-account')->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testHandleActivateFailed()
    {
        $handler = new ActivateAccountHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->userServiceProphecy->reveal()
        );

        $this->userServiceProphecy->activate('tok123')->willReturn(false);

        $this->templateRendererProphecy->render('actor::activate-account-not-found')->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
