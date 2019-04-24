<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\ViewLpaHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class ViewLpaHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $lpa = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::view-lpa', [
                'lpa' => $lpa,
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaById(123456789)
            ->willReturn($lpa);

        //  Set up the handler
        $handler = new ViewLpaHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('id')
            ->willReturn('123456789');

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
