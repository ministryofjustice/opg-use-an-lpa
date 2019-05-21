<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Prophecy\Argument\Token\CallbackToken;
use Prophecy\Prophecy\ObjectProphecy;
use Viewer\Form\ShareCode;
use Viewer\Handler\EnterCodeHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class EnterCodeHandlerTest extends TestCase
{
    const CSRF_CODE="1234";

    public function testSimplePageGet()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        /** @var ServerRequestInterface|ObjectProphecy $requestProphecy */
        $requestProphecy = $this->getRequestProphecy();
        $requestProphecy->getMethod()
            ->willReturn("GET");
        $requestProphecy->getParsedBody()
            ->willReturn([]);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmitted()
    {
        $lpaId = '123456789012';

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $urlHelperProphecy->generate('check-code', [], [])
            ->willReturn('/check-code' . $lpaId);

        $lpa = new ArrayObject([
            'id' => $lpaId
        ], ArrayObject::ARRAY_AS_PROPS);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        /** @var ServerRequestInterface|ObjectProphecy $requestProphecy */
        $requestProphecy = $this->getRequestProphecy();
        $requestProphecy->getMethod()
            ->willReturn("POST");
        $requestProphecy->getParsedBody()
            ->willReturn([
                'lpa_code' => '1234-5678-9012',
                '__csrf'   => self::CSRF_CODE
            ]);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedNoLpaFound()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        /** @var ServerRequestInterface|ObjectProphecy $requestProphecy */
        $requestProphecy = $this->getRequestProphecy();
        $requestProphecy->getMethod()
            ->willReturn("POST");
        $requestProphecy->getParsedBody()
            ->willReturn(['lpa_code' => '1234-5678-9012']);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * @return ObjectProphecy
     */
    private function getRequestProphecy()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('code', '1234-5678-9012');

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('session', null)
            ->willReturn($sessionProphecy->reveal());
        $requestProphecy->getAttribute('csrf')
            ->willReturn($csrfProphecy->reveal());

        return $requestProphecy;
    }
}
