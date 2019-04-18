<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\HomePageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class HomePageHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::home-page')
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        //  Set up the handler
        $homePage = new HomePageHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        $response = $homePage->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
