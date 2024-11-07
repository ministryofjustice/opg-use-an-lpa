<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\Sirius\SiriusLpa;
use Common\Entity\Sirius\SiriusLpaAttorney;
use Common\Entity\Sirius\SiriusLpaDonor;
use Common\Entity\Sirius\SiriusLpaTrustCorporations;
use Common\Enum\LpaType;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\WhenTheLpaCanBeUsed;
use Common\Service\Features\FeatureEnabled;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
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
        $combinedFormat    = $this->getMockedCombinedFormat();
        $expectedSiriusLpa = $this->expectedSiriusLpa();
        $expectedSiriusLpa->activeAttorneys = $this->expectedAttorneys();

        $this->lpaFactory->createLpaFromData($this->lpaData['lpa'])->willReturn($combinedFormat);
        $this->lpaFactory->createCaseActorFromData($this->lpaData['actor']['details'])->willReturn($this->actor);
        $this->instAndPrefImagesFactory->createFromData($this->lpaData['iap'])->willReturn($this->iapImages);
        $this->lpaDataFormatter->__invoke($combinedFormat)->willReturn($expectedSiriusLpa);

        $sut = new ParseLpaData(
            $this->lpaFactory->reveal(),
            $this->instAndPrefImagesFactory->reveal(),
            $this->lpaDataFormatter->reveal(),
            $this->featureEnabled->reveal()
        );

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $this->lpaData['lpa'] = $combinedFormat;
        $result = $sut(
            $this->lpaData
        );

        $this->assertEquals($expectedSiriusLpa, $result->lpa);
    }

    private function getMockedCombinedFormat(): array
    {
        return [
            'id' => 2,
            'uId' => '700000000047',
            'receiptDate' => '2014-09-26',
            'registrationDate' => '2019-10-10',
            'rejectedDate' => null,
            'donor' => [
                'id' => 7,
                'uId' => '700000000799',
                'linked' => [['id' => 7, 'uId' => '700000000799']],
                'dob' => '1948-11-01',
                'email' => 'RachelSanderson@opgtest.com',
                'salutation' => 'Mr',
                'firstname' => 'Rachel',
                'middlenames' => 'Emma',
                'surname' => 'Sanderson',
                'addresses' => [
                    [
                        'id' => 7,
                        'town' => '',
                        'county' => '',
                        'postcode' => 'DN37 5SH',
                        'country' => '',
                        'type' => 'Primary',
                        'addressLine1' => '81 Front Street',
                        'addressLine2' => 'LACEBY',
                        'addressLine3' => '',
                    ],
                ],
                'companyName' => null,
            ],
            'applicationType' => 'Classic',
            'caseSubtype' => 'hw',
            'status' => 'Registered',
            'lpaIsCleansed' => true,
            'caseAttorneySingular' => false,
            'caseAttorneyJointlyAndSeverally' => true,
            'caseAttorneyJointly' => false,
            'caseAttorneyJointlyAndJointlyAndSeverally' => false,
            'onlineLpaId' => 'A33718377316',
            'cancellationDate' => null,
            'attorneys' => [
                [
                    'id' => 9,
                    'uId' => '700000000815',
                    'dob' => '1990-05-04',
                    'email' => '',
                    'salutation' => '',
                    'firstname' => 'jean',
                    'middlenames' => '',
                    'surname' => 'sanderson',
                    'addresses' => [
                        [
                            'id' => 9,
                            'town' => '',
                            'county' => '',
                            'postcode' => 'DN37 5SH',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '9 high street',
                            'addressLine2' => '',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName' => '',
                ],
                [
                    'id' => 12,
                    'uId' => '7000-0000-0849',
                    'dob' => '1975-10-05',
                    'email' => 'XXXXX',
                    'salutation' => 'Mrs',
                    'firstname' => 'Ann',
                    'middlenames' => '',
                    'surname' => 'Summers',
                    'addresses' => [
                        [
                            'id' => 12,
                            'town' => '',
                            'county' => '',
                            'postcode' => '',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName' => '',
                ],
            ],
            'replacementAttorneys' => [],
            'trustCorporations' => [
                [
                    'addresses' => [
                        [
                            'id' => 3207,
                            'town' => 'Town',
                            'county' => 'County',
                            'postcode' => 'ABC 123',
                            'country' => 'GB',
                            'type' => 'Primary',
                            'addressLine1' => 'Street 1',
                            'addressLine2' => 'Street 2',
                            'addressLine3' => 'Street 3',
                        ],
                    ],
                    'id' => 3485,
                    'uId' => '7000-0015-1998',
                    'dob' => null,
                    'email' => null,
                    'salutation' => null,
                    'firstname' => 'trust',
                    'middlenames' => null,
                    'surname' => 'test',
                    'otherNames' => null,
                    'systemStatus' => true,
                    'companyName' => 'trust corporation',
                ],
            ],
            'certificateProviders' => [
                [
                    'id' => 11,
                    'uId' => '7000-0000-0831',
                    'dob' => null,
                    'email' => null,
                    'salutation' => 'Miss',
                    'firstname' => 'Danielle',
                    'middlenames' => null,
                    'surname' => 'Hart ',
                    'addresses' => [
                        [
                            'id' => 11,
                            'town' => '',
                            'county' => '',
                            'postcode' => 'SK14 0RH',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '50 Fordham Rd',
                            'addressLine2' => 'HADFIELD',
                            'addressLine3' => '',
                        ],
                    ],
                ],
            ],
            'attorneyActDecisions' => null,
            'applicationHasRestrictions' => false,
            'applicationHasGuidance' => false,
            'lpaDonorSignatureDate' => '2012-12-12',
            'lifeSustainingTreatment' => 'Option A',
            'whenTheLpaCanBeUsed' => 'when-has-capacity'
        ];
    }

    public function expectedSiriusLpa(): SiriusLpa
    {
        return new SiriusLpa(
            applicationHasGuidance:      false,
            applicationHasRestrictions:  false,
            applicationType            : 'Classic',
            attorneyActDecisions       : null,
            attorneys:                   $this->expectedAttorneys(),
            caseSubtype      : LpaType::fromShortName('personal-welfare'),
            channel          : null,
            dispatchDate     : null,
            donor            : new SiriusLpaDonor(
                addressLine1 : '81 Front Street',
                addressLine2 : 'LACEBY',
                addressLine3 : '',
                country      : '',
                county       : '',
                dob          : new DateTimeImmutable('1948-11-01'),
                email        : 'RachelSanderson@opgtest.com',
                firstname    : 'Rachel',
                firstnames   : null,
                linked       : [
                    [
                        'id'  => 7,
                        'uId' => '700000000799',
                    ],
                ],
                name         : null,
                otherNames   : null,
                postcode     : 'DN37 5SH',
                surname      : 'Sanderson',
                systemStatus : null,
                town         : '',
                type         : 'Primary',
                uId          : '700000000799'
            ),
            hasSeveranceWarning     : null,
            invalidDate             : null,
            lifeSustainingTreatment : LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate   : new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed           : true,
            onlineLpaId             : 'A33718377316',
            receiptDate             : new DateTimeImmutable('2014-09-26'),
            registrationDate        : new DateTimeImmutable('2019-10-10'),
            rejectedDate            : null,
            replacementAttorneys    : [],
            status                  : 'Registered',
            statusDate              : null,
            trustCorporations       : [
                new SiriusLpaTrustCorporations(
                    addressLine1 : 'Street 1',
                    addressLine2 : 'Street 2',
                    addressLine3 : 'Street 3',
                    country      : 'GB',
                    county       : 'County',
                    dob          : null,
                    email        : null,
                    firstname    : 'trust',
                    firstnames   : null,
                    name         : null,
                    otherNames   : null,
                    postcode     : 'ABC 123',
                    surname      : 'test',
                    systemStatus : '1',
                    town         : 'Town',
                    type         : 'Primary',
                    uId          : '7000-0015-1998',
                ),
            ],
            uId                     : '700000000047',
            withdrawnDate           : null,
            whenTheLpaCanBeUsed     : WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY
        );
    }

    public function expectedAttorneys(): array
    {
        return [
            new SiriusLpaAttorney(
                addressLine1 : '9 high street',
                addressLine2 : '',
                addressLine3 : '',
                country      : '',
                county       : '',
                dob          : new DateTimeImmutable('1990-05-04'),
                email        : '',
                firstname    : 'jean',
                firstnames   : null,
                name         : null,
                otherNames   : null,
                postcode     : 'DN37 5SH',
                surname      : 'sanderson',
                systemStatus : '1',
                town         : '',
                type         : 'Primary',
                uId          : '700000000815'
            ),
            new SiriusLpaAttorney(
                addressLine1       : '',
                addressLine2       : '',
                addressLine3       : '',
                country            : '',
                county             : '',
                dob                : new DateTimeImmutable('1975-10-05'),
                email              : 'XXXXX',
                firstname          : 'Ann',
                firstnames         : null,
                name               : null,
                otherNames         : null,
                postcode           : '',
                surname            : 'Summers',
                systemStatus       : '1',
                town               : '',
                type               : 'Primary',
                uId                : '7000-0000-0849'
            ),
        ];
    }

}
