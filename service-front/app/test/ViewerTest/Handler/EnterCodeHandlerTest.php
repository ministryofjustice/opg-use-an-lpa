<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\EnterCodeHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class EnterCodeHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', [
                'errorMsg' => null,
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('test', 'hello');

        $requestProphecy = $this->getRequestProphecy('GET');

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostNoLpaFound()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
            $rendererProphecy->render('app::enter-code', [
                'errorMsg' => 'No LPA were found using the provided code',
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $requestProphecy = $this->getRequestProphecy('POST', [
            'share-code' => '67890',
        ]);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostLpaFound()
    {
        $lpaId = '12345678901';

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $urlHelperProphecy->generate('view-lpa', [
                'id' => $lpaId,
            ], [])
            ->willReturn('/view-lpa/' . $lpaId);

        $lpa = new ArrayObject([
            'id' => $lpaId
        ], ArrayObject::ARRAY_AS_PROPS);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaByCode('12345')
            ->willReturn($lpa);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal());

        $requestProphecy = $this->getRequestProphecy('POST', [
            'share-code' => '12345',
        ]);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    private function getRequestProphecy(string $requestMethod, array $bodyData = [])
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('test', 'hello');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()
            ->willReturn($requestMethod);

        $requestProphecy->getAttribute('session', null)
            ->willReturn($sessionProphecy->reveal());

        $requestProphecy->getParsedBody()
            ->willReturn($bodyData);

        return $requestProphecy;
    }
}
