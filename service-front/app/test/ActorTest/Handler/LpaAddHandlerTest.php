<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\LpaAdd;
use Actor\Handler\LpaAddHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class LpaAddHandlerTest extends TestCase
{
    const CSRF_CODE = '1234';

    private $rendererProphecy;
    private $urlHelperProphecy;
    private $requestProphecy;
    private $authenticatorProphecy;

    public function setUp()
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->rendererProphecy->render('actor::lpa-add', new CallbackToken(function($options) {
                $this->assertIsArray($options);
                $this->assertArrayHasKey('form', $options);
                $this->assertInstanceOf(LpaAdd::class, $options['form']);

                return true;
            }))
            ->willReturn('');

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());
    }

    public function testGetReturnsHtmlResponse()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        //  Set up the handler
        $handler = new LpaAddHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidHtmlResponse()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE,
                'passcode' => '',
                'reference_number' => '',
                'dob' => [
                    'day' => '',
                    'month' => '',
                    'year' => '',
                ],
            ]);

        //  Set up the handler
        $handler = new LpaAddHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
