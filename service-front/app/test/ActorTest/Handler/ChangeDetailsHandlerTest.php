<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\ChangeDetailsHandler;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class ChangeDetailsHandlerTest extends TestCase
{

    const LPA_ID = '98765432-12345-54321-12345-9876543210';

    /**
     * @var ObjectProphecy|TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var ObjectProphecy|UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var ObjectProphecy|AuthenticationInterface
     */
    private $authenticatorProphecy;

    /**
     * @var ObjectProphecy|ServerRequestInterface
     */
    private $requestProphecy;

    public function setUp()
    {
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
    }

    public function test_change_details_page_will_render_with_valid_actor_token()
    {
        $handler = new ChangeDetailsHandler(
            $this->templateRendererProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $this->templateRendererProphecy
            ->render('actor::change-details', [
                'actorToken' => self::LPA_ID,
                'user' => null
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
