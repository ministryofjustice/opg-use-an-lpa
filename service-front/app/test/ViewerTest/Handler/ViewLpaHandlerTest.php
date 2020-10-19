<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Entity\Lpa;
use Common\Service\Lpa\LpaService;
use Viewer\Handler\ViewLpaHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Session\SessionInterface;
use ArrayObject;

class ViewLpaHandlerTest extends TestCase
{
    const TEST_LPA_CODE = '1234-5678-9012';
    const TEST_SURNAME = 'test_surname';
    const TEST_ORGANISATION = 'test_organisation';

    /** @test */
    public function it_returns_an_html_response_when_appropriate_session_data_in_place()
    {
        $lpa = new Lpa();
        $lpa->setUId('700000000047');

        $lpaData = new ArrayObject(['expires' => '2019-12-12', 'lpa' => $lpa], ArrayObject::ARRAY_AS_PROPS);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('viewer::view-lpa', [
                'lpa' => $lpaData->lpa,
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaByCode(self::TEST_LPA_CODE, self::TEST_SURNAME, self::TEST_ORGANISATION)
            ->willReturn($lpaData);

        //  Set up the handler
        $handler = new ViewLpaHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $sessionProphecy = $this->prophesize(SessionInterface::class );
        $sessionProphecy->get('code')->willReturn(self::TEST_LPA_CODE);
        $sessionProphecy->get('surname')->willReturn(self::TEST_SURNAME);
        //$sessionProphecy->get('organisation')->willReturn(self::TEST_SURNAME);

        $requestProphecy->getAttribute('session', null)->willReturn($sessionProphecy->reveal());
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
