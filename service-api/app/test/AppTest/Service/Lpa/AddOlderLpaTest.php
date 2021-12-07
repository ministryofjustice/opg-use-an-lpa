<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\AddOlderLpa;
use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use App\Service\Lpa\ValidateOlderLpaRequirements;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AddOlderLpaTest extends TestCase
{
    /** @var ObjectProphecy|FindActorInLpa */
    private $findActorInLpaProphecy;

    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|LpaAlreadyAdded */
    private $lpaAlreadyAddedProphecy;

    /** @var ObjectProphecy|OlderLpaService */
    private $olderLpaServiceProphecy;

    /** @var ObjectProphecy|ValidateOlderLpaRequirements */
    private $validateOlderLpaRequirementsProphecy;

    /** @var ObjectProphecy|RestrictSendingLpaForCleansing */
    private $restrictSendingLpaForCleansingProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    private string $userId;
    private string $lpaUid;
    private array $dataToMatch;
    private array $resolvedActor;
    private Lpa $lpa;
    private array $lpaData;

    public function setUp(): void
    {
        $this->findActorInLpaProphecy = $this->prophesize(FindActorInLpa::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->lpaAlreadyAddedProphecy = $this->prophesize(LpaAlreadyAdded::class);
        $this->olderLpaServiceProphecy = $this->prophesize(OlderLpaService::class);
        $this->validateOlderLpaRequirementsProphecy = $this->prophesize(ValidateOlderLpaRequirements::class);
        $this->restrictSendingLpaForCleansingProphecy = $this->prophesize(RestrictSendingLpaForCleansing::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = '700000012345';

        $this->lpa = $this->older_lpa_get_by_uid_response();
        $this->lpaData = $this->lpa->getData();

        $this->dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Test Tester', // lpa attorney
            'last_name'             => 'Testing',
            'postcode'              => 'Ab1 2Cd',
            'force_activation_key'  => false,
        ];

        $this->resolvedActor = [
            'lpa-id'     => $this->lpaUid,
            'caseSubtype' => 'pfa',
            'actor'     => $this->lpaData['attorneys'][1],
            'role'      => 'attorney',
            'attorney'       => [
                'uId'         => $this->lpaData['attorneys'][1]['uId'],
                'firstname'   => $this->lpaData['attorneys'][1]['firstname'],
                'middlenames' => $this->lpaData['attorneys'][1]['middlenames'],
                'surname'     => $this->lpaData['attorneys'][1]['surname'],
            ],
            'donor'       => [
                'uId'         => $this->lpaData['donor']['uId'],
                'firstname'   => 'Donor',
                'middlenames' => 'Example',
                'surname'     => 'Person',
            ]
        ];
    }

    protected function getSut(): AddOlderLpa
    {
        return new AddOlderLpa(
            $this->findActorInLpaProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->lpaAlreadyAddedProphecy->reveal(),
            $this->olderLpaServiceProphecy->reveal(),
            $this->validateOlderLpaRequirementsProphecy->reveal(),
            $this->restrictSendingLpaForCleansingProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->featureEnabledProphecy->reveal()

        );
    }

    /** @test */
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->olderLpaServiceProphecy
            ->hasActivationCode($this->lpaUid, $this->lpaData['attorneys'][1]['uId'])
            ->willReturn(null);

        $result = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($this->lpaUid, $result['lpa-id']);
        $this->assertEquals($this->lpaData['donor']['uId'], $result['donor']['uId']);
        $this->assertEquals($this->lpaData['donor']['firstname'], $result['donor']['firstname']);
        $this->assertEquals($this->lpaData['donor']['middlenames'], $result['donor']['middlenames']);
        $this->assertEquals($this->lpaData['donor']['surname'], $result['donor']['surname']);
        $this->assertEquals($this->lpaData['caseSubtype'], $result['caseSubtype']);
        $this->assertEquals($this->lpaData['attorneys'][1], $result['actor']);
        $this->assertEquals('attorney', $result['role']);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_added()
    {
        $alreadyAddedData = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'qwerty-54321'
        ];

        $expectedException = new BadRequestException('LPA already added', $alreadyAddedData);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_requested()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);
        $alreadyAddedData = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'qwerty-54321',
            'notActivated'  => true
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $expectedException = new BadRequestException('Activation key already requested for LPA', $alreadyAddedData);

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_successful_if_lpa_already_requested_but_force_flag_true()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);
        $this->dataToMatch['force_activation_key'] = true;

        $alreadyAddedData = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'qwerty-54321',
            'notActivated'  => true
        ];

        $expectedResponse = [
            'lpa-id'        => $this->lpaUid,
            'caseSubtype'   => 'pfa',
            'actor'         => $this->lpaData['donor'],
            'role'          => 'donor',
            'lpaActorToken' => 'qwerty-54321',
            'donor'       => [
                'uId'         => $this->lpaData['donor']['uId'],
                'firstname'   => 'Donor',
                'middlenames' => 'Example',
                'surname'     => 'Person'
            ]
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($expectedResponse);

        $response = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
        $this->assertEquals($expectedResponse, $response);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found()
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_date_not_valid()
    {
        $invalidLpa = new Lpa(
            [
                'uId'               => $this->lpaUid,
                'registrationDate'  => '2019-08-31',
                'status'            => 'Registered',
            ],
            new DateTime()
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($invalidLpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($invalidLpa->getData())
            ->willThrow(new BadRequestException('LPA not eligible due to registration date'));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_status_not_registered()
    {
        $invalidLpa = new Lpa(
            [
                'uId'               => $this->lpaUid,
                'registrationDate'  => '2019-08-31',
                'status'            => 'Registered',
            ],
            new DateTime()
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($invalidLpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($invalidLpa->getData())
            ->willThrow(new NotFoundException('LPA status invalid'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA status invalid');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_user_data_doesnt_match_lpa()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);
        $dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Wrong Name',
            'last_name'             => 'Incorrect',
            'postcode'              => 'wR0 nG1',
            'force_activation_key'  => false,
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details do not match');

        $this->getSut()->validateRequest($this->userId, $dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_exception_if_lpa_already_has_activation_key()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->olderLpaServiceProphecy
            ->hasActivationCode($this->lpaUid, $this->lpaData['attorneys'][1]['uId'])
            ->willReturn(new DateTime());

        $expectedException = new BadRequestException(
            'LPA has an activation key already',
            [
                'donor'         => $this->resolvedActor['donor'],
                'caseSubtype'   => $this->resolvedActor['caseSubtype']
            ]
        );

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_exception_if_lpa_already_has_activation_key_but_force_flag_true()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(false);
        $this->dataToMatch['force_activation_key'] = true;

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $result = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($this->lpaUid, $result['lpa-id']);
        $this->assertEquals($this->lpaData['donor']['uId'], $result['donor']['uId']);
        $this->assertEquals($this->lpaData['donor']['firstname'], $result['donor']['firstname']);
        $this->assertEquals($this->lpaData['donor']['middlenames'], $result['donor']['middlenames']);
        $this->assertEquals($this->lpaData['donor']['surname'], $result['donor']['surname']);
        $this->assertEquals($this->lpaData['caseSubtype'], $result['caseSubtype']);
        $this->assertEquals($this->lpaData['attorneys'][1], $result['actor']);
        $this->assertEquals('attorney', $result['role']);
    }

    /**
     * Returns the lpa data needed for checking in the older LPA journey
     *
     * @return Lpa
     */
    public function older_lpa_get_by_uid_response(): Lpa
    {
        $attorney1 = [
            'uId'       => '700000002222',
            'dob'       => '1977-11-21',
            'firstname' => 'Attorneyone',
            'surname'   => 'Person',
            'addresses' => [
                [
                    'postcode' => 'Gg1 2ff'
                ]
            ],
            'systemStatus' => false,
        ];

        $attorney2 = [
            'uId'       => '700000055554',
            'dob'       => '1980-03-01',
            'firstname' => 'Test',
            'surname'   => 'Testing',
            'addresses' => [
                [
                    'postcode' => 'Ab1 2Cd'
                ]
            ],
            'systemStatus' => true,
        ];

        return new Lpa(
            [
                'uId'               => $this->lpaUid,
                'registrationDate'  => '2016-01-01',
                'status'            => 'Registered',
                'lpaIsCleansed'     => false,
                'caseSubtype'       => 'pfa',
                'donor' => [
                    'uId'           => '700000001111',
                    'dob'           => '1975-10-05',
                    'firstname'     => 'Donor',
                    'middlenames'   => 'Example',
                    'surname'       => 'Person',
                    'addresses'     => [
                        [
                            'postcode' => 'PY1 3Kd'
                        ]
                    ]
                ],
                'attorneys' => [
                    $attorney1,
                    $attorney2
                ]
            ],
            new DateTime()
        );
    }

    /** @test */
    public function older_lpa_lookup_throws_not_found_exception_if_lpa_registered_after_2019_and_restrict_flag_true()
    {
        $this->featureEnabledProphecy
            ->__invoke('dont_send_lpas_registered_before_sep_2019_to_cleansing_team_enabled')
            ->willReturn(true);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($this->lpaData);

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn(null);

        $this->restrictSendingLpaForCleansingProphecy
            ->__invoke($this->lpaData, null)
            ->willThrow(new NotFoundException('LPA not found'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }
}
