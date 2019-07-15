<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Exception\ApiException;
use Common\Service\Lpa\LpaService;
use Psr\Http\Message\StreamInterface;
use Viewer\Handler\CheckCodeHandler;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\SessionInterface;
use Prophecy\Argument;
use ArrayObject;

class CheckCodeHandlerTest extends TestCase
{
    const TEST_CODE = 'test-code';

    /**
     * @var TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var LpaService
     */
    private $lpaServiceProphecy;

    /**
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    /**
     * @var SessionInterface
     */
    private $sessionProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        // The Session
        $this->sessionProphecy = $this->prophesize(SessionInterface::class);
        $this->sessionProphecy->get('code')->willReturn(self::TEST_CODE);
        $this->requestProphecy->getAttribute('session', Argument::any())->willreturn($this->sessionProphecy->reveal());
    }

    /**
     * Tests the case where an invalid (not found) code is within the session.
     * We expect the 'Invalid Code' template.
     */
    public function testInvalidCode()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        //---

        // Return null. i.e. code not found.
        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE)->willReturn(null);

        $this->templateRendererProphecy->render('viewer::check-code-not-found', Argument::any())->willReturn('');

        //---

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * Tests the case where an expired code is within the session.
     * We expect the 'Expired Code' template.
     */
    public function testExpiredCode()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        //---
        // Throw 410 exception
        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE)
            ->willThrow($this->getException(410));

        $this->templateRendererProphecy->render('viewer::check-code-expired', Argument::any())
            ->willReturn('');
        //---

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * Tests the case the a valid code is passed within the session.
     * We expect the code to be looked up, and a valid response.
     * Then we expect the confirmation template.
     */
    public function testValidCode()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        //---

        $lpa = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE)->willReturn($lpa);

        //---

        $this->templateRendererProphecy->render('viewer::check-code-found',
            ['lpa' => $lpa]
        )->willReturn('');

        //---

        $response = $handler->handle($this->requestProphecy->reveal());
        $this->assertInstanceOf(HtmlResponse::class, $response);

    }

    /**
     * @param int $code
     * @param array $body
     * @return ApiException
     */
    private function getException(int $code, array $body = [])
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()->willReturn(json_encode($body));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->getStatusCode()->willReturn($code);

        return ApiException::create(null, $responseProphecy->reveal());
    }
}
