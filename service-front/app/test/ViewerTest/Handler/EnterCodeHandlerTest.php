<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Form\ShareCode;
use Viewer\Handler\EnterCodeHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class EnterCodeHandlerTest extends TestCase
{
    const CSRF_CODE = "1234";

    /**
     * @var TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('code', '1234-5678-9012');

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);

        $this->requestProphecy->getAttribute('session', null)
            ->willReturn($sessionProphecy->reveal());
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());
    }

    public function testSimplePageGet()
    {
        $this->templateRendererProphecy->render('viewer::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new EnterCodeHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("GET");

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmitted()
    {
        $this->templateRendererProphecy->render('viewer::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))->willReturn('');

        $this->urlHelperProphecy->generate('check-code', [], [])
            ->willReturn('/check-code');

        //  Set up the handler
        $handler = new EnterCodeHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn([
                'lpa_code' => '1234-5678-9012',
                '__csrf'   => self::CSRF_CODE
            ]);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFormSubmittedNoLpaFound()
    {
        $this->templateRendererProphecy->render('viewer::enter-code', new CallbackToken(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('form', $options);
            $this->assertInstanceOf(ShareCode::class, $options['form']);

            return true;
        }))->willReturn('');

        //  Set up the handler
        $handler = new EnterCodeHandler($this->templateRendererProphecy->reveal(), $this->urlHelperProphecy->reveal());

        $this->requestProphecy->getMethod()
            ->willReturn("POST");
        $this->requestProphecy->getParsedBody()
            ->willReturn(['lpa_code' => '1234-5678-9012']);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
