<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\HomePageHandler;
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
        $rendererProphecy->render('actor::home-page')
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        //  Set up the handler
        $handler = new HomePageHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
