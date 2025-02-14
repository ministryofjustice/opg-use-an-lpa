<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\{CaseActor,
    CombinedLpa,
    InstructionsAndPreferences\Images,
    InstructionsAndPreferences\ImagesStatus,
    Lpa as SiriusLpa,
    Person};
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\{Factory\LpaDataFormatter,
    Factory\PersonDataFormatter,
    InstAndPrefImagesFactory,
    LpaFactory,
    ParseLpaData};
use CommonTest\Helper\EntityTestHelper;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ParseLpaData::class)]
class ParseLpaDataTest extends TestCase
{
    use ProphecyTrait;

    private string $actorToken;
    private array $apiResponse;
    private string $lpaId;
    private ObjectProphecy|LpaFactory $lpaFactory;
    private ObjectProphecy|InstAndPrefImagesFactory $instAndPrefImagesFactory;

    private ObjectProphecy|LpaDataFormatter $lpaDataFormatter;
    private ObjectProphecy|FeatureEnabled $featureEnabled;
    private ObjectProphecy|PersonDataFormatter $personDataFormatter;

    public function setUp(): void
    {
        $this->actorToken = '34-3-3-3-3456';
        $this->lpaId      = '78-7-7-7-7891';

        $this->apiResponse = [
            'user-actor-lpa-token' => $this->actorToken,
            'lpa'                  => ['uId' => $this->lpaId],
            'actor'                => [
                'type'    => 'primary-attorney',
                'details' => ['uId' => '56-5-5-5-5678'],
            ],
        ];

        $this->lpaFactory               = $this->prophesize(LpaFactory::class);
        $this->instAndPrefImagesFactory = $this->prophesize(InstAndPrefImagesFactory::class);
        $this->lpaDataFormatter         = $this->prophesize(LpaDataFormatter::class);
        $this->personDataFormatter      = $this->prophesize(PersonDataFormatter::class);
        $this->featureEnabled           = $this->prophesize(FeatureEnabled::class);
    }

    #[Test]
    public function it_correctly_parses_an_lpa_api_response(): void
    {
        $this->lpaFactory
            ->createLpaFromData(Argument::type('array'))
            ->willReturn(EntityTestHelper::makeSiriusLpa());
        $this->lpaFactory
            ->createCaseActorFromData(Argument::type('array'))
            ->willReturn(EntityTestHelper::makeCaseActor());

        $this->instAndPrefImagesFactory
            ->createFromData(Argument::type('array'))
            ->willReturn(
                new Images(
                    700000000047,
                    ImagesStatus::COLLECTION_COMPLETE,
                    [],
                ),
            );

        $apiResponse        = $this->apiResponse;
        $apiResponse['iap'] = ['uId' => $this->lpaId];

        $sut = new ParseLpaData(
            $this->lpaFactory->reveal(),
            $this->instAndPrefImagesFactory->reveal(),
            $this->lpaDataFormatter->reveal(),
            $this->personDataFormatter->reveal(),
            $this->featureEnabled->reveal()
        );

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(false);

        $result = $sut(
            [
                $this->actorToken => $apiResponse,
            ]
        );

        $this->assertObjectHasProperty($this->actorToken, $result);
        $this->assertInstanceOf(SiriusLpa::class, $result->{$this->actorToken}->lpa);
        $this->assertInstanceOf(CaseActor::class, $result->{$this->actorToken}->actor['details']);
        $this->assertInstanceOf(Images::class, $result->{$this->actorToken}->iap);
    }

    #[Test]
    public function it_correctly_parses_an_combined_lpa_api_response(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);
        $this->lpaDataFormatter
            ->__invoke(Argument::type('array'))
            ->willReturn(EntityTestHelper::makeCombinedLpa());
        $this->personDataFormatter
            ->__invoke(Argument::type('array'))
            ->willReturn(EntityTestHelper::makePerson());

        $sut = new ParseLpaData(
            $this->lpaFactory->reveal(),
            $this->instAndPrefImagesFactory->reveal(),
            $this->lpaDataFormatter->reveal(),
            $this->personDataFormatter->reveal(),
            $this->featureEnabled->reveal()
        );

        $result = $sut(
            [
                $this->actorToken => $this->apiResponse,
            ]
        );

        $this->assertObjectHasProperty($this->actorToken, $result);
        $this->assertInstanceOf(CombinedLpa::class, $result->{$this->actorToken}->lpa);
        $this->assertInstanceOf(Person::class, $result->{$this->actorToken}->actor['details']);
    }
}
