<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\CheckLpaHandler;
use Common\Entity\Lpa;
use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\Factory\Sirius as LpaFactory;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class CheckLpaHandlerTest extends TestCase
{
    const CSRF_CODE="1234";
    const TEST_PASSCODE = 'test-passcode';
    const TEST_REF_NUMBER = 'test-ref-number';
    const TEST_DOB = '1980-01-01';

    /**
     * @var ObjectProphecy|TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var ObjectProphecy|UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var ObjectProphecy|LpaService
     */
    private $lpaServiceProphecy;

    /**
     * @var ObjectProphecy|AuthenticationInterface
     */
    private $authenticatorProphecy;

    /**
     * @var ObjectProphecy|ServerRequestInterface
     */
    private $requestProphecy;

    /**
     * @var ObjectProphecy|SessionInterface
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

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)->willReturn(true);
        $csrfProphecy->validateToken('badcode')->willReturn(false);

        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());
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

    /** @test */
    public function will_show_a_confirmation_page_if_valid_details_given_as_donor()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('GET');

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $lpa = (new LpaFactory())->createLpaFromData([
            'uId' => '700000000047',
            'donor' => [
                'uId' => '700000000082',
                'dob' => '1980-01-01'
            ]
        ]);

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn($lpa);

        $this->templateRendererProphecy->render('actor::check-lpa', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function will_show_a_confirmation_page_if_valid_details_given_as_attorney()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('GET');

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $lpa = (new LpaFactory())->createLpaFromData([
            'uId' => '700000000047',
            'donor' => [
                'uId' => '700000000082',
                'dob' => '1975-01-01'
            ],
            'attorneys' => [
                [
                    'uId' => '700000000023',
                    'dob' => '1980-01-01'
                ]
            ]
        ]);

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn($lpa);

        $this->templateRendererProphecy->render('actor::check-lpa', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function empty_identity_results_in_session_error()
    {
        $this->authenticatorProphecy->authenticate($this->requestProphecy->reveal())->willReturn(null);

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->expectException(SessionTimeoutException::class);

        $handler->handle($this->requestProphecy->reveal());
    }

    /** @test */
    public function empty_password_detail_results_in_session_error()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

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

    /** @test */
    public function no_lpa_found_when_details_incorrect()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('GET');

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willThrow($this->getException(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->templateRendererProphecy->render('actor::lpa-not-found', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function it_propagates_api_errors_correctly()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('GET');

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willThrow($this->getException(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $this->expectException(ApiException::class);

        $handler->handle($this->requestProphecy->reveal());
    }

    /** @test */
    public function it_confirms_an_lpa_when_post_occurs()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE
            ]);

        $this->urlHelperProphecy->generate(Argument::type('string'))->willReturn('http://localhost');

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->confirmLpaAddition(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn('actor-lpa-code');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /** @test */
    public function it_returns_form_errors_when_invalid_form_is_submitted()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => 'badcode'
            ]);

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $lpa = (new LpaFactory())->createLpaFromData([
            'uId' => '700000000047',
            'donor' => [
                'uId' => '700000000082',
                'dob' => '1980-01-01'
            ]
        ]);

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn($lpa);

        $this->templateRendererProphecy->render('actor::check-lpa', Argument::any())->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /** @test */
    public function it_returns_a_session_timeout_when_posting_is_valid_but_incorrect()
    {
        $actorId = '01234567-0123-0123-0123-012345678901';

        $this->authenticateRequest($actorId);

        $this->requestProphecy->getMethod()->willReturn('POST');

        $this->requestProphecy->getParsedBody()
            ->willReturn([
                '__csrf' => self::CSRF_CODE
            ]);

        $handler = new CheckLpaHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->lpaServiceProphecy->confirmLpaAddition(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn(null);

        $this->lpaServiceProphecy->getLpaByPasscode(
            $actorId,
            self::TEST_PASSCODE,
            self::TEST_REF_NUMBER,
            self::TEST_DOB)->willReturn(null);

        $this->expectException(SessionTimeoutException::class);

        $response = $handler->handle($this->requestProphecy->reveal());
    }

    private function authenticateRequest(string $actorId)
    {
        $identity = $this->prophesize(User::class);
        $identity->getIdentity()->willReturn($actorId);
        $this->authenticatorProphecy->authenticate($this->requestProphecy->reveal())->willReturn($identity->reveal());
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
