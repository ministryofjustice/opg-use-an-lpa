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
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\MethodProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property string            userToken
 * @property string            actorToken
 * @property ViewerCodeService viewerCodeServiceProphecy
 * @property ArrayObject       lpas
 * @property CaseActor         lpaActor
 */
#[CoversClass(PopulateLpaMetadata::class)]
class PopulateLpaMetadataTest extends TestCase
{
    use ProphecyTrait;

    public string $userToken;
    public string $actorToken;
    public array $siriusLpa;
    public array $combinedLpa;
    public ViewerCodeService|ObjectProphecy $viewerCodeServiceProphecy;

    /**
     * @throws InterfaceNotFoundException
     * @throws UnableToHydrateObject
     * @throws DoubleException
     * @throws MethodProphecyException
     */
    public function setUp(): void
    {
        $this->userToken  = '12-1-1-1-1234';
        $this->actorToken = '34-3-3-3-3456';

        $this->viewerCodeServiceProphecy = $this->prophesize(ViewerCodeService::class);
        $this->viewerCodeServiceProphecy
            ->getShareCodes($this->userToken, $this->actorToken, true)
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
    public function it_correctly_handles_lpas_that_werent_found(): void
    {
        $lpas = $this->getLpas(
            [
                $this->actorToken => $this->siriusLpa,
                '56-5-5-5-5678'   => [
                    'user-lpa-actor-token' => '56-5-5-5-5678',
                    'error'                => 'NO_LPA_FOUND',
                ],
            ]
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($lpas, $this->userToken);

        $this->assertObjectHasProperty($this->actorToken, $result);
        $this->assertEquals(1, $result->{$this->actorToken}->{'activeCodeCount'});
    }

    #[Test]
    public function it_adds_a_viewer_code_count_per_lpa(): void
    {
        $lpas = $this->getLpas(
            [
                $this->actorToken => $this->siriusLpa,
            ]
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($lpas, $this->userToken);

        $this->assertObjectHasProperty($this->actorToken, $result);
        $this->assertEquals(1, $result->{$this->actorToken}->{'activeCodeCount'});
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

        $sut    = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($lpas, $this->userToken);

        $this->assertObjectHasProperty('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->actorActive);
    }

    #[Test]
    public function it_always_makes_a_donor_be_active(): void
    {
        $lpaData    = new ArrayObject(
            [
                'user-lpa-actor-token' => $this->actorToken,
                'actor'                => [
                    'type'    => 'donor',
                    'details' => $this->lpaActor,
                ],
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
        $this->lpas = new ArrayObject(
            [
                '56-5-5-5-5678' => $lpaData,
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $sut    = new PopulateLpaMetadata($this->viewerCodeServiceProphecy->reveal());
        $result = $sut($this->lpas, $this->userToken);

        $this->assertObjectHasProperty('56-5-5-5-5678', $result);
        $this->assertEquals(true, $result->{'56-5-5-5-5678'}->{'actorActive'});
    }
}
