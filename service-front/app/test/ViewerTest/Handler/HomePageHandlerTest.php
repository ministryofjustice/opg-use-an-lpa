<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\HomePageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

class HomePageHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('viewer::home-page')
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        //  Set up the handler
        $handler = new HomePageHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
