<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CombinedLpa;
use Common\Entity\LpaStore\LpaStore;
use Common\Entity\LpaStore\LpaStoreAttorney;
use Common\Entity\LpaStore\LpaStoreDonor;
use Common\Entity\LpaStore\LpaStoreTrustCorporations;
use Common\Entity\Person;
use Common\Entity\Sirius\SiriusLpa;
use Common\Entity\Sirius\SiriusLpaAttorney;
use Common\Entity\Sirius\SiriusLpaDonor;
use Common\Entity\Sirius\SiriusLpaTrustCorporations;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\Factory\PersonDataFormatter;
use CommonTest\Helper\EntityTestHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
use Common\Service\Lpa\Factory\LpaDataFormatter;

/**
 * @property array     lpaData
 * @property string    actorToken
 * @property string    lpaId
 * @property string    actorId
 * @property Lpa       lpa
 * @property CaseActor actor
 * @property Images    iapImages
 */
#[CoversClass(ParseLpaData::class)]
class ParseLpaDataTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|LpaFactory $lpaFactory;
    private ObjectProphecy|InstAndPrefImagesFactory $instAndPrefImagesFactory;

    private ObjectProphecy|LpaDataFormatter $lpaDataFormatter;
    private ObjectProphecy|FeatureEnabled $featureEnabled;
    private ObjectProphecy|PersonDataFormatter $personDataFormatter;

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
                'signedUrls' => [],
            ],
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
        $this->lpaDataFormatter         = $this->prophesize(LpaDataFormatter::class);
        $this->personDataFormatter      = $this->prophesize(PersonDataFormatter::class);
        $this->featureEnabled           = $this->prophesize(FeatureEnabled::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_correctly_parses_an_lpa_api_response(): void
    {
        $this->lpaFactory->createLpaFromData($this->lpaData['lpa'])->willReturn($this->lpa);
        $this->lpaFactory->createCaseActorFromData($this->lpaData['actor']['details'])->willReturn($this->actor);

        $this->instAndPrefImagesFactory->createFromData($this->lpaData['iap'])->willReturn($this->iapImages);

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
                $this->lpaId => $this->lpaData,
            ]
        );

        $this->assertObjectHasProperty($this->lpaId, $result);
        $this->assertEquals($this->lpa, $result->{$this->lpaId}->lpa);
        $this->assertEquals($this->actor, $result->{$this->lpaId}->actor['details']);
        $this->assertEquals($this->iapImages, $result->{$this->lpaId}->iap);
    }

    #[Test]
    public function it_correctly_parses_an_combined_lpa_api_response(): void
    {
        $combinedFormat = json_decode(file_get_contents(__DIR__ . '../../../../fixtures/combined_lpa.json'), true);

        $this->lpaFactory->createLpaFromData($this->lpaData['lpa'])->willReturn($combinedFormat);
        $this->lpaFactory->createCaseActorFromData($this->lpaData['actor']['details'])->willReturn($this->actor);
        $this->instAndPrefImagesFactory->createFromData($this->lpaData['iap'])->willReturn($this->iapImages);
        $this->lpaDataFormatter->__invoke($combinedFormat)->willReturn($this->expectedLpa());

        $sut = new ParseLpaData(
            $this->lpaFactory->reveal(),
            $this->instAndPrefImagesFactory->reveal(),
            $this->lpaDataFormatter->reveal(),
            $this->personDataFormatter->reveal(),
            $this->featureEnabled->reveal()
        );

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $this->lpaData['lpa'] = $combinedFormat;
        $result               = $sut($this->lpaData);

        $this->assertEquals($this->expectedLpa(), $result->lpa);
    }

    public function expectedLpa(): CombinedLpa
    {
        $attorneys = [
            new Person(
                addressLine1 : '9 high street',
                addressLine2 : '',
                addressLine3 : '',
                country      : '',
                county       : '',
                dob          : new DateTimeImmutable('1990-05-04'),
                email        : '',
                firstnames   : null,
                name         : null,
                otherNames   : null,
                postcode     : 'DN37 5SH',
                surname      : 'sanderson',
                systemStatus : '1',
                town         : '',
                uId          : '700000000815'
            ),
            new Person(
                addressLine1       : '',
                addressLine2       : '',
                addressLine3       : '',
                country            : '',
                county             : '',
                dob                : new DateTimeImmutable('1975-10-05'),
                email              : 'XXXXX',
                firstnames         : null,
                name               : null,
                otherNames         : null,
                postcode           : '',
                surname            : 'Summers',
                systemStatus       : '1',
                town               : '',
                uId                : '7000-0000-0849'
            ),
        ];

        $donor = new Person(
            addressLine1 : '81 Front Street',
            addressLine2 : 'LACEBY',
            addressLine3 : '',
            country      : '',
            county       : '',
            dob          : new DateTimeImmutable('1948-11-01'),
            email        : 'RachelSanderson@opgtest.com',
            firstnames   : null,
            name         : null,
            otherNames   : null,
            postcode     : 'DN37 5SH',
            surname      : 'Sanderson',
            systemStatus : null,
            town         : '',
            uId          : '700000000799'
        );

        $trustCorporations = [
            new Person(
                addressLine1 : 'Street 1',
                addressLine2 : 'Street 2',
                addressLine3 : 'Street 3',
                country      : 'GB',
                county       : 'County',
                dob          : null,
                email        : null,
                firstnames   : null,
                name         : null,
                otherNames   : null,
                postcode     : 'ABC 123',
                surname      : 'test',
                systemStatus : '1',
                town         : 'Town',
                uId          : '7000-0015-1998',
            ),
        ];

        return EntityTestHelper::makeSiriusLpa(
            attorneys:         $attorneys,
            donor:             $donor,
            trustCorporations: $trustCorporations,
        );
    }
}
