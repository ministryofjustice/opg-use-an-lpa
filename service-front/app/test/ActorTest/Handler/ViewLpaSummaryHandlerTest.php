<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Actor\Handler\ViewLpaSummaryHandler;
use Common\Entity\Lpa;
use Common\Exception\InvalidRequestException;
use Common\Exception\ApiException;
use Prophecy\Argument;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;

class ViewLpaSummaryHandlerTest extends TestCase
{
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const LPA_ID = '98765432-12345-54321-12345-9876543210';

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
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $userProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->templateRendererProphecy->render('actor::view-lpa-summary', Argument::that(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('actorToken', $options);
            $this->assertArrayHasKey('lpa', $options);
            $this->assertArrayHasKey('user', $options);
            return true;
        }))
            ->willReturn('');

        $this->userProphecy = $this->prophesize(UserInterface::class);
        $this->userProphecy->getIdentity()->willReturn(self::IDENTITY_TOKEN);
    }

    public function test_will_show_lpa_summary_with_valid_lpa_id()
    {
        $actorLpa = [
            'type' => 'donor',
            'details' => [
                'email' => 'babaragilson@opgtest.com',
            ]
        ];

        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
        ->willReturn($this->userProphecy->reveal());

        $handler = new ViewLpaSummaryHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $lpa = new Lpa();

        $this->lpaServiceProphecy
            ->getLpaById(self::IDENTITY_TOKEN, self::LPA_ID)
            ->willReturn([$lpa, $actorLpa]);

        $this->templateRendererProphecy
            ->render('actor:view-lpa-summary', [
                'actorToken' => self::LPA_ID,
                'user' => self::IDENTITY_TOKEN,
                'lpa' => $lpa,
                'actor' => $actorLpa,
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function test_lpa_not_found_will_throw_exception()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new ViewLpaSummaryHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $this->lpaServiceProphecy
            ->getLpaById(self::IDENTITY_TOKEN, self::LPA_ID)
            ->willThrow(new ApiException('Error whilst making http GET request', 404));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error whilst making http GET request');
        $this->expectExceptionCode(404);

        $handler->handle($this->requestProphecy->reveal());
    }

    public function test_will_throw_error_if_token_is_null()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new ViewLpaSummaryHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn(null);

        $this->expectException(InvalidRequestException::class);

        $handler->handle($this->requestProphecy->reveal());

    }
}
