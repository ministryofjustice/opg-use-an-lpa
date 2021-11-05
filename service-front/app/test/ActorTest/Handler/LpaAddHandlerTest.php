<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\LpaAdd;
use Actor\Handler\LpaAddHandler;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;

class LpaAddHandlerTest extends TestCase
{
    const CSRF_CODE = '1234';

    /**
     * @var TemplateRendererInterface
     */
    private $rendererProphecy;

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
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->sessionProphecy = $this->prophesize(SessionInterface::class);

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());

        $this->requestProphecy->getAttribute('session', null)
            ->willReturn($this->sessionProphecy->reveal());
    }

    public function testGetReturnsHtmlResponse()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        $this->rendererProphecy->render('actor::lpa-add', new CallbackToken(function($options) {
                $this->assertIsArray($options);
                $this->assertArrayHasKey('form', $options);
                $this->assertInstanceOf(LpaAdd::class, $options['form']);

                return true;
            }))
            ->willReturn('');

        //  Set up the handler
        $handler = new LpaAddHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal(), $this->lpaServiceProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidData()
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

        $this->rendererProphecy->render('actor::lpa-add', new CallbackToken(function($options) {
                $this->assertIsArray($options);
                $this->assertArrayHasKey('form', $options);
                $this->assertInstanceOf(LpaAdd::class, $options['form']);

                return true;
            }))
            ->willReturn('');

        //  Set up the handler
        $handler = new LpaAddHandler($this->rendererProphecy->reveal(), $this->urlHelperProphecy->reveal(), $this->authenticatorProphecy->reveal(), $this->lpaServiceProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * @dataProvider validSubmissions
     * @test
     */
    public function redirects_with_all_valid_submissions(array $expected, string $dob)
    {
        $this->requestProphecy->getMethod()
            ->willReturn('POST');

        $this->sessionProphecy->set('passcode', $expected['passcode']);
        $this->sessionProphecy->set('reference_number', $expected['reference_number']);
        $this->sessionProphecy->set('dob_by_code', $dob);

        $this->requestProphecy->getParsedBody()
            ->willReturn($expected);

        $this->urlHelperProphecy->generate('lpa.check', [], [])
            ->willReturn('/lpa/check');

        //  Set up the handler
        $handler = new LpaAddHandler(
            $this->rendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal());

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function validSubmissions(): array
    {
        return [
            [
               [
                    '__csrf' => self::CSRF_CODE,
                    'passcode' => '100000000001',
                    'reference_number' => '700000000001',
                    'dob' => [
                        'day' => '01',
                        'month' => '01',
                        'year' => '1980',
                    ]
                ],
                '1980-01-01'
            ],
            [
                [
                    '__csrf' => self::CSRF_CODE,
                    'passcode' => '100000000001',
                    'reference_number' => '700000000001',
                    'dob' => [
                        'day' => '1',
                        'month' => '01',
                        'year' => '1980',
                    ]
                ],
                '1980-01-01'
            ],
            [
                [
                    '__csrf' => self::CSRF_CODE,
                    'passcode' => '100000000001',
                    'reference_number' => '700000000001',
                    'dob' => [
                        'day' => '01',
                        'month' => '1',
                        'year' => '1980',
                    ]
                ],
                '1980-01-01'
            ],
            [
                [
                    '__csrf' => self::CSRF_CODE,
                    'passcode' => '100000000001',
                    'reference_number' => '700000000001',
                    'dob' => [
                        'day' => '10',
                        'month' => '11',
                        'year' => '1980',
                    ]
                ],
                '1980-11-10'
            ]
        ];
    }
}
