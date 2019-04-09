<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\HomePageHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class HomePageHandlerTest extends TestCase
{
    /**
     * @var TemplateRendererInterface|ObjectProphecy
     */
    protected $renderer;

    protected function setUp()
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
    }

    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $homePage = new HomePageHandler();

        //  Mimic the behaviour of the initializer
        $this->renderer
            ->render('app::home-page')
            ->willReturn('');

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->renderer->reveal();

        $homePage->setTemplateRenderer($renderer);

        /** @var ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $response = $homePage->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
