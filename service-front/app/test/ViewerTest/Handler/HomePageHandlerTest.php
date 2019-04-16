<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\HomePageHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class HomePageHandlerTest extends TestCase
{
    /**
     * @var TemplateRendererInterface|ObjectProphecy
     */
    protected $renderer;

    /**
     * @var UrlHelper|ObjectProphecy
     */
    protected $urlHelper;

    protected function setUp()
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelper = $this->prophesize(UrlHelper::class);
    }

    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        //  Set up the handler
        $this->renderer
            ->render('app::home-page')
            ->willReturn('');

        $homePage = new HomePageHandler($this->renderer->reveal(), $this->urlHelper->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $response = $homePage->handle($request->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
