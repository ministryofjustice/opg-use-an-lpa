<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Entity\Lpa;
use Common\Service\Lpa\InstAndPrefImagesFactory;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\ParseLpaData;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \Common\Service\Lpa\ParseLpaData
 * @property           array     lpaData
 * @property           string    actorToken
 * @property           string    lpaId
 * @property           string    actorId
 * @property           Lpa       lpa
 * @property           CaseActor actor
 * @property           Images    iapImages
 */
class ParseLpaDataTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|LpaFactory $lpaFactory;
    private ObjectProphecy|InstAndPrefImagesFactory $instAndPrefImagesFactory;

    public function setUp(): void
    {
        $this->actorToken = '34-3-3-3-3456';
        $this->actorId    = '56-5-5-5-5678';
        $this->lpaId      = '78-7-7-7-7891';

        $this->lpaData = [
            'user-lpa-actor-token' => $this->actorToken,
            'actor'                => [
                'type'    => 'attorney',
                'details' => [
                    'uId' => $this->actorId,
                ],
            ],
            'lpa'                  => [
                'uId' => $this->lpaId,
            ],
            'iap'                  => [
                'uId'        => $this->lpaId,
                'status'     => 'COLLECTION_COMPLETE',
                'signedUrls' => []
            ]
        ];

        $this->lpa = new Lpa();
        $this->lpa->setUId($this->lpaId);

        $this->actor = new CaseActor();
        $this->actor->setUId($this->actorId);

        $this->iapImages = new Images(
            (int) $this->lpaData['iap']['uId'],
            ImagesStatus::from($this->lpaData['iap']['status']),
            $this->lpaData['iap']['signedUrls'],
        );

        $this->lpaFactory               = $this->prophesize(LpaFactory::class);
        $this->instAndPrefImagesFactory = $this->prophesize(InstAndPrefImagesFactory::class);
    }

    /**
     * @test
     * @covers ::__invoke
     * @throws Exception
     */
    public function it_correctly_parses_an_lpa_api_response(): void
    {
        $this->lpaFactory->createLpaFromData($this->lpaData['lpa'])->willReturn($this->lpa);
        $this->lpaFactory->createCaseActorFromData($this->lpaData['actor']['details'])->willReturn($this->actor);

        $this->instAndPrefImagesFactory->createFromData($this->lpaData['iap'])->willReturn($this->iapImages);

        $sut    = new ParseLpaData($this->lpaFactory->reveal(), $this->instAndPrefImagesFactory->reveal());
        $result = $sut(
            [
                $this->lpaId => $this->lpaData,
            ]
        );
        $this->assertObjectHasAttribute($this->lpaId, $result);
        $this->assertEquals($this->lpa, $result->{$this->lpaId}->lpa);
        $this->assertEquals($this->actor, $result->{$this->lpaId}->actor['details']);
        $this->assertEquals($this->iapImages, $result->{$this->lpaId}->iap);
    }
}
