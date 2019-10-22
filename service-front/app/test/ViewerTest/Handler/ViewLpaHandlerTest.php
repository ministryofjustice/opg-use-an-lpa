<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Service\Lpa\LpaService;
use Viewer\Handler\ViewLpaHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Session\SessionInterface;
use ArrayObject;

class ViewLpaHandlerTest extends TestCase
{
    const TEST_LPA_CODE = '1234-5678-9012';
    const TEST_SURNAME = 'test_surname';

    public function testSimplePageGetReturnsHtmResponse()
    {
        $lpa = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('viewer::view-lpa', [
                'lpa' => $lpa->lpa,
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaByCode(self::TEST_LPA_CODE, self::TEST_SURNAME)
            ->willReturn($lpa);

        //  Set up the handler
        $handler = new ViewLpaHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $sessionProphecy = $this->prophesize(SessionInterface::class );
        $sessionProphecy->get('code')->willReturn(self::TEST_LPA_CODE);
        $sessionProphecy->get('surname')->willReturn(self::TEST_SURNAME);

        $requestProphecy->getAttribute('session', null)->willReturn($sessionProphecy->reveal());
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
