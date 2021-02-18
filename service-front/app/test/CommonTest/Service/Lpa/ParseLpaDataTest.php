<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\ParseLpaData;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property array     lpaData
 * @property string    actorToken
 * @property string    lpaId
 * @property string    actorId
 * @property Lpa       lpa
 * @property CaseActor actor
 */
class ParseLpaDataTest extends TestCase
{
    /** @var ObjectProphecy|LpaFactory */
    private $lpaFactory;

    public function setUp(): void
    {
        $this->actorToken = '34-3-3-3-3456';
        $this->actorId = '56-5-5-5-5678';
        $this->lpaId = '78-7-7-7-7891';

        $this->lpaData = [
            'user-lpa-actor-token' => $this->actorToken,
            'actor' => [
                'type' => 'attorney',
                'details' => [
                    'uId' => $this->actorId
                ],
            ],
            'lpa' => [
                'uId' => $this->lpaId
            ]
        ];

        $this->lpa = new Lpa();
        $this->lpa->setUId($this->lpaId);

        $this->actor = new CaseActor();
        $this->actor->setUId($this->actorId);

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
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

        $sut = new ParseLpaData($this->lpaFactory->reveal());
        $result = $sut(
            [
                $this->lpaId => $this->lpaData
            ]
        );

        $this->assertObjectHasAttribute($this->lpaId, $result);
        $this->assertEquals($this->lpa, $result->{$this->lpaId}->lpa);
        $this->assertEquals($this->actor, $result->{$this->lpaId}->actor['details']);
    }
}
