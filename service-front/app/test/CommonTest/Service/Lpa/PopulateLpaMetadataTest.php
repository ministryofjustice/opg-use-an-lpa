<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Service\Lpa\Factory\PersonDataFormatter;
use Common\Service\Lpa\PopulateLpaMetadata;
use Common\Service\Lpa\ViewerCodeService;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PopulateLpaMetadata::class)]
class PopulateLpaMetadataTest extends TestCase
{
    public string $actorToken;

    /** @var array<string, mixed> */
    public array $siriusLpa;

    /** @var array<string, mixed> */
    public array $combinedLpa;
    public ViewerCodeService&MockObject $viewerCodeService;

    /**
     * @throws UnableToHydrateObject
     */
    public function setUp(): void
    {
        $this->actorToken = '34-3-3-3-3456';

        $this->viewerCodeService = $this->createMock(ViewerCodeService::class);
        $this->viewerCodeService
            ->expects($this->atLeastOnce())
            ->method('getShareCodes')
            ->with($this->actorToken, true)
            ->willReturn(
                new ArrayObject(
                    [
                        'activeCodeCount' => 1,
                    ],
                    ArrayObject::ARRAY_AS_PROPS
                ),
            );

        $siriusActor = new CaseActor();
        $siriusActor->setSystemStatus(true);
        $this->siriusLpa = [
            'user-lpa-actor-token' => $this->actorToken,
            'actor'                => [
                'type'    => 'primary-attorney',
                'details' => $siriusActor,
            ],
        ];

        $this->combinedLpa = [
            'user-lpa-actor-token' => $this->actorToken,
            'actor'                => [
                'type'    => 'primary-attorney',
                'details' => (new PersonDataFormatter())(
                    [
                        'uid'          => 'person-uid',
                        'systemStatus' => 'active',
                    ],
                ),
            ],
        ];
    }

    /**
     * For a reason in the dim and distant past we used array objects for things that end up being passed to the
     * frontend twig code. This isn't actually necessary but until we refactor that way this method will be needed
     * to make sure we've formatted the data as expected.
     */
    private function getLpas($lpaData): ArrayObject
    {
        array_walk(
            $lpaData,
            fn (&$value) => $value = new ArrayObject($value, ArrayObject::ARRAY_AS_PROPS)
        );

        return new ArrayObject($lpaData, ArrayObject::ARRAY_AS_PROPS);
    }

    #[Test]
    public function it_adds_a_viewer_code_count_per_lpa(): void
    {
        $lpas = $this->getLpas(
            [
                $this->actorToken => $this->siriusLpa,
            ]
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeService);
        $result = $sut($lpas);

        $this->assertObjectHasProperty($this->actorToken, $result);
        $this->assertEquals(1, $result->{$this->actorToken}->activeCodeCount);
    }

    #[Test]
    public function it_adds_an_active_status_for_actor(): void
    {
        $lpas = $this->getLpas(
            [
                $this->actorToken => $this->siriusLpa,
                '56-5-5-5-5678'   => $this->combinedLpa,
            ]
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeService);
        $result = $sut($lpas);

        $this->assertObjectHasProperty('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->actorActive);
    }

    #[Test]
    public function it_should_mark_an_inactive_attorney_as_inactive(): void
    {
        $actor = new CaseActor();
        $actor->setSystemStatus(false);
        $this->siriusLpa['actor']['details'] = $actor;

        $lpas = $this->getLpas([$this->actorToken => $this->siriusLpa]);

        $sut    = new PopulateLpaMetadata($this->viewerCodeService);
        $result = $sut($lpas);

        $this->assertFalse($result->{$this->actorToken}->actorActive);
    }

    #[Test]
    public function it_always_makes_a_donor_be_active(): void
    {
        $lpaData = new ArrayObject(
            [
                'user-lpa-actor-token' => $this->actorToken,
                'actor'                => [
                    'type'    => 'donor',
                    'details' => new CaseActor(),
                ],
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
        $lpas    = new ArrayObject(
            [
                '56-5-5-5-5678' => $lpaData,
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeService);
        $result = $sut($lpas);

        $this->assertObjectHasProperty('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->actorActive);
    }
}
