<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\CheckAccessCodesHandler;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Common\Service\Lpa\ViewerCodeService;
use ArrayObject;
use Common\Exception\InvalidRequestException;

class CheckAccessCodesHandlerTest extends TestCase
{
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const LPA_ID = '98765432-12345-54321-12345-9876543210';
    const ACTOR_ID = 10;
    const FIRST_NAME = "John";
    const SUR_NAME = "Will";

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

    /**
     * @var ObjectProphecy|ViewerCodeService
     */
    private $viewerCodeServiceProphecy;

    public function setUp()
    {
        // Constructor Parameters
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);
        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);

        // The request
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->templateRendererProphecy->render('actor::check-access-codes', Argument::that(function($options) {
            $this->assertIsArray($options);
            $this->assertArrayHasKey('actorToken', $options);
            $this->assertArrayHasKey('user', $options);
            $this->assertArrayHasKey('lpa', $options);
            $this->assertArrayHasKey('shareCodes', $options);
            return true;
        }))
            ->willReturn('');

        $this->userProphecy = $this->prophesize(UserInterface::class);
        $this->userProphecy->getIdentity()->willReturn(self::IDENTITY_TOKEN);
    }

    /** @test */
    public function check_access_codes_page_is_displayed()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new CheckAccessCodesHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $lpa = new Lpa();

        $donor = new CaseActor();
        $donor->setId(self::ACTOR_ID);
        $attorney = new CaseActor();
        $attorney->setId(5);

        $lpa->setDonor($donor);
        $lpa->setAttorneys([$attorney]);

        $shareCodes = new ArrayObject([['ActorId' => self::ACTOR_ID]], ArrayObject::ARRAY_AS_PROPS);

        $this->lpaServiceProphecy
            ->getLpaById(self::IDENTITY_TOKEN, self::LPA_ID)
            ->willReturn($lpa);

        $this->viewerCodeServiceProphecy
            ->getShareCodes(self::IDENTITY_TOKEN, self::LPA_ID, false)
            ->willReturn($shareCodes);

        $this->templateRendererProphecy
            ->render('actor:check-access-codes', [
                'actorToken' => self::LPA_ID,
                'user' => self::IDENTITY_TOKEN,
                'lpa' => $lpa,
                'shareCodes' => $shareCodes
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    //test needed for if the actorLpaToken is null
    public function test_will_throw_error_if_token_is_null()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new CheckAccessCodesHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => null
            ]);

        $this->expectException(InvalidRequestException::class);

        $handler->handle($this->requestProphecy->reveal());

    }

    //test needed for ensuring that the ‘createdBy’ field is set correctly as both the donor or attorney’s name
    public function test_createdBy_field_set_correctly_as_both_the_donor_or_attorney_name()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new CheckAccessCodesHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $lpa = new Lpa();

        $donor = new CaseActor();
        $donor->setId(self::ACTOR_ID);
        $donor->setFirstname(self::FIRST_NAME);
        $attorney = new CaseActor();
        $attorney->setId(15);
        $attorney->setFirstname(self::FIRST_NAME);
        $attorney->setSurname(self::SUR_NAME);

        $lpa->setDonor($donor);
        $lpa->setAttorneys([$attorney]);

        $shareCodes = new ArrayObject([['ActorId' => self::ACTOR_ID,'CreatedBy' => self::FIRST_NAME]], ArrayObject::ARRAY_AS_PROPS);


        $this->lpaServiceProphecy
            ->getLpaById(self::IDENTITY_TOKEN, self::LPA_ID)
            ->willReturn($lpa);

        $this->viewerCodeServiceProphecy
            ->getShareCodes(self::IDENTITY_TOKEN, self::LPA_ID, false)
            ->willReturn($shareCodes);

        $this->templateRendererProphecy
            ->render('actor:check-access-codes', [
                'actorToken' => self::LPA_ID,
                'user' => self::IDENTITY_TOKEN,
                'lpa' => $lpa,
                'shareCodes' => $shareCodes
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        foreach ($shareCodes as $key => $code) {
            if ($lpa->getDonor()->getId() == $code['ActorId']) {
                $this->assertEquals($code['CreatedBy'], $lpa->getDonor()->getFirstname());
            }
            if ($attorney->getId() == $code['ActorId']) {
                $this->assertEquals($code['CreatedBy'], $attorney->getFirstname() . ' ' . $attorney->getSurname());
            }
        }
    }

    public function test_createdBy_field_set_correctly_as_both_the_donor_or_attorney_name_when_actor_id_same()
    {
        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());

        $handler = new CheckAccessCodesHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->viewerCodeServiceProphecy->reveal()
        );

        $this->requestProphecy->getQueryParams()
            ->willReturn([
                'lpa' => self::LPA_ID
            ]);

        $lpa = new Lpa();

        $donor = new CaseActor();
        $donor->setId(self::ACTOR_ID);
        $donor->setFirstname(self::FIRST_NAME);
        $attorney = new CaseActor();
        $attorney->setId(self::ACTOR_ID);
        $attorney->setFirstname(self::FIRST_NAME);
        $attorney->setSurname(self::SUR_NAME);

        $lpa->setDonor($donor);
        $lpa->setAttorneys([$attorney]);

        $shareCodes = new ArrayObject([['ActorId' => self::ACTOR_ID,'CreatedBy' => self::FIRST_NAME]], ArrayObject::ARRAY_AS_PROPS);


        $this->lpaServiceProphecy
            ->getLpaById(self::IDENTITY_TOKEN, self::LPA_ID)
            ->willReturn($lpa);

        $this->viewerCodeServiceProphecy
            ->getShareCodes(self::IDENTITY_TOKEN, self::LPA_ID, false)
            ->willReturn($shareCodes);

        $this->templateRendererProphecy
            ->render('actor:check-access-codes', [
                'actorToken' => self::LPA_ID,
                'user' => self::IDENTITY_TOKEN,
                'lpa' => $lpa,
                'shareCodes' => $shareCodes
            ])
            ->willReturn('');

        $response = $handler->handle($this->requestProphecy->reveal());

        foreach ($shareCodes as $key => $code) {
            if ($attorney->getId() == $code['ActorId']) {
                $this->assertEquals($code['CreatedBy'], $attorney->getFirstname() . ' ' . $attorney->getSurname());
            }
        }

    }
}