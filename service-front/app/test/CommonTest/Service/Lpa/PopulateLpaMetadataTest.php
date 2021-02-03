<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Service\Lpa\PopulateLpaMetadata;
use Common\Service\Lpa\ViewerCodeService;
use PHPUnit\Framework\TestCase;

/**
 * Class PopulateLpaMetadataTest
 *
 * @property string            userToken
 * @property string            actorToken
 * @property ViewerCodeService viewerCodeServiceProphecy
 * @property ArrayObject       lpas
 * @property CaseActor         lpaActor
 *
 * @coversDefaultClass \Common\Service\Lpa\PopulateLpaMetadata
 * @package CommonTest\Service\Lpa
 */
class PopulateLpaMetadataTest extends TestCase
{
    /**
     * @before
     */
    public function setupFixtures(): void
    {
        $this->userToken = '12-1-1-1-1234';
        $this->actorToken = '34-3-3-3-3456';
        $codes = new ArrayObject(
            [
                'activeCodeCount' => 1
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
        $this->lpaActor = new CaseActor();
        $this->lpaActor->setSystemStatus(true);

        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);
        $this->viewerCodeServiceProphecy
            ->getShareCodes($this->userToken, $this->actorToken, true)
            ->willReturn($codes);

        $lpaData = new ArrayObject(
            [
                'user-lpa-actor-token' => $this->actorToken,
                'actor' => [
                    'type' => 'attorney',
                    'details' => $this->lpaActor,
                ],
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
        $this->lpas = new ArrayObject(
            [
                '56-5-5-5-5678' => $lpaData
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_adds_a_viewer_code_count_per_lpa(): void
    {
        $sut = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($this->lpas, $this->userToken);

        $this->assertObjectHasAttribute('56-5-5-5-5678', $result);
        $this->assertEquals(1, $result->{'56-5-5-5-5678'}->{'activeCodeCount'});
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_adds_an_active_status_for_actor(): void
    {
        $sut = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($this->lpas, $this->userToken);

        $this->assertObjectHasAttribute('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->{'actorActive'});
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_always_makes_a_donor_be_active(): void
    {
        $lpaData = new ArrayObject(
            [
                'user-lpa-actor-token' => $this->actorToken,
                'actor' => [
                    'type' => 'donor',
                    'details' => $this->lpaActor,
                ],
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
        $this->lpas = new ArrayObject(
            [
                '56-5-5-5-5678' => $lpaData
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $sut = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($this->lpas, $this->userToken);

        $this->assertObjectHasAttribute('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->{'actorActive'});
    }
}
