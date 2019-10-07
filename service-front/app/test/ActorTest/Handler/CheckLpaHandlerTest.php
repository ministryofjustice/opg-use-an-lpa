<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\CheckLpaHandler;
use Common\Exception\ApiException;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class CheckLpaHandlerTest extends TestCase
{
    const TEST_PASSCODE = 'test-passcode';
    const TEST_REF_NUMBER = 'test-ref-number';
    const TEST_DOB = '1980-01-01';

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
     * @var AuthenticationInterface
     */
    private $authenticatorProphecy;

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
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        // The Session
        $this->sessionProphecy = $this->prophesize(SessionInterface::class);
        $this->sessionProphecy->get('passcode')->willReturn(self::TEST_PASSCODE);
        $this->sessionProphecy->get('reference_number')->willReturn(self::TEST_REF_NUMBER);
        $this->sessionProphecy->get('dob')->willReturn(self::TEST_DOB);
        $this->requestProphecy->getAttribute('session', Argument::any())->willreturn($this->sessionProphecy->reveal());
    }

    /**
     * Iterates an array recursively to turn it into an array object.
     *
     * @param array $in
     * @return ArrayObject
     */
    public function recursiveArrayToArrayObject(array $in)
    {
        foreach ($in as $dataItemName => $dataItem) {
            if (is_array($dataItem)) {
                $in[$dataItemName] = $this->recursiveArrayToArrayObject($dataItem);
            }
        }

        return new ArrayObject($in, ArrayObject::ARRAY_AS_PROPS);
    }

    public function testValidDonorDetails()
    {
        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn($this->recursiveArrayToArrayObject(
                [
                    'actor' => [],
                    'lpa' => [
                        'donor' => [
                            'dob' => '1980-01-01'
                        ]
                    ]
                ]
            )
        );

        $this->templateRendererProphecy->render('actor::check-lpa', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testValidAttorneyDetails()
    {
        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn($this->recursiveArrayToArrayObject(
                [
                    'actor' => [],
                    'lpa' => [
                        'donor' => [
                            'dob' => '1980-01-01'
                        ]
                    ]
                ]
            )
        );

        $this->templateRendererProphecy->render('actor::check-lpa', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidDetailsTimeout()
    {
        $this->sessionProphecy->get('passcode')->willReturn(null);

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->expectException(SessionTimeoutException::class);

        $handler->handle($this->requestProphecy->reveal());
    }

    public function testDetailsNotFound()
    {
        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willThrow($this->getException(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->templateRendererProphecy->render('actor::lpa-not-found', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testDetailsServerException()
    {
        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willThrow($this->getException(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $this->expectException(ApiException::class);

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
