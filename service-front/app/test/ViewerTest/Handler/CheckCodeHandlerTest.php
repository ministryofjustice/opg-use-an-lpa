<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Exception\ApiException;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use phpDocumentor\Reflection\Types\String_;
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
    const TEST_SURNAME = 'test-surname';

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
        $this->requestProphecy->getAttribute('session', Argument::any())->willreturn($this->sessionProphecy->reveal());
    }

    /**
     * Tests the case where an invalid (not found) code is within the session
     * And/or the donor's surname does not match the code.
     * We expect the 'LPA not found' template.
     */
    public function testInvalidCodeAndOrNotMatchingSurname()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        //---

        // Return null. i.e. code not found or donors surname doesn't match (if code was valid)
        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE, self::TEST_SURNAME)->willReturn(null);

        $this->templateRendererProphecy->render('viewer::check-code-not-found', Argument::any())->willReturn('');

        $this->sessionProphecy->get('code')->willReturn(self::TEST_CODE);
        $this->sessionProphecy->get('surname')->willReturn(self::TEST_SURNAME);

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
        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE, self::TEST_SURNAME)
            ->willThrow($this->getException(410));

        $this->templateRendererProphecy->render('viewer::check-code-expired', Argument::any())
            ->willReturn('');

        $this->sessionProphecy->get('code')->willReturn(self::TEST_CODE);
        $this->sessionProphecy->get('surname')->willReturn(self::TEST_SURNAME);

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * Tests the case the a valid code and matching donor surname is passed within the session
     * We expect the code to be looked up, and a valid response.
     * Then we expect the confirmation template.
     */
    public function testValidCodeAndSurnameMatches()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        //---

        $lpa = new ArrayObject(['expires' => '2019-12-12'], ArrayObject::ARRAY_AS_PROPS);

        $this->lpaServiceProphecy->getLpaByCode(self::TEST_CODE, self::TEST_SURNAME)->willReturn($lpa);

        //---

        $this->templateRendererProphecy->render('viewer::check-code-found',
            ['lpa'     => $lpa->lpa,
             'expires' => $lpa->expires]
        )->willReturn('');

        $this->sessionProphecy->get('code')->willReturn(self::TEST_CODE);
        $this->sessionProphecy->get('surname')->willReturn(self::TEST_SURNAME);

        $response = $handler->handle($this->requestProphecy->reveal());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidDetailsTimeout()
    {
        $handler = new CheckCodeHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->sessionProphecy->get('code')->willReturn(null);
        $this->sessionProphecy->get('surname')->willReturn(null);

        $this->expectException(SessionTimeoutException::class);

        $handler->handle($this->requestProphecy->reveal());
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
