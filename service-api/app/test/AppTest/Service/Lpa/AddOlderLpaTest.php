<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\AddOlderLpa;
use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
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

    /** @var ObjectProphecy|ValidateOlderLpaRequirements */
    private $validateOlderLpaRequirementsProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    private string $userId;
    private string $lpaUid;
    private string $actorUid;

    public function setUp(): void
    {
        $this->findActorInLpaProphecy = $this->prophesize(FindActorInLpa::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->lpaAlreadyAddedProphecy = $this->prophesize(LpaAlreadyAdded::class);
        $this->validateOlderLpaRequirementsProphecy = $this->prophesize(ValidateOlderLpaRequirements::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = '700000012345';
        $this->actorUid = '700000055554';
    }

    protected function getSut(): AddOlderLpa
    {
        return new AddOlderLpa(
            $this->findActorInLpaProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->lpaAlreadyAddedProphecy->reveal(),
            $this->validateOlderLpaRequirementsProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
    }

    /** @test */
    public function checks_if_lpa_already_added_and_throws_exception_if_yes()
    {
        $responseData = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'qwerty-54321',
        ];

        $expectedException = new BadRequestException('LPA already added', $responseData);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn($responseData);

        $this->expectExceptionObject($expectedException);

        $this->getSut()->validateRequest($this->userId, ['reference_number' => $this->lpaUid]);
    }

    /** @test */
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria()
    {
        $dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Test Tester',
            'last_name'             => 'Testing',
            'postcode'              => 'Ab1 2Cd',
            'force_activation_key'  => false,
        ];

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn();

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData())
            ->willReturn(true);

        $this->findActorInLpaProphecy
            ->__invoke($lpa->getData(), $dataToMatch)
            ->willReturn(
                [
                    'actor-id'    => $this->actorUid,
                    'lpa-id'     => $this->lpaUid,
                    'donor'       => [
                        'uId'         => '700000001111',
                        'firstname'   => 'Donor',
                        'middlenames' => 'Example',
                        'surname'     => 'Person',
                    ],
                    'caseSubtype' => 'pfa'
                ]
            );

        $result = $this->getSut()->validateRequest($this->userId, $dataToMatch);

        $this->assertEquals($this->actorUid, $result['actor-id']);
        $this->assertEquals($this->lpaUid, $result['lpa-id']);
        $this->assertEquals($lpa->getData()['donor']['uId'], $result['donor']['uId']);
        $this->assertEquals($lpa->getData()['donor']['firstname'], $result['donor']['firstname']);
        $this->assertEquals($lpa->getData()['donor']['middlenames'], $result['donor']['middlenames']);
        $this->assertEquals($lpa->getData()['donor']['surname'], $result['donor']['surname']);
        $this->assertEquals($lpa->getData()['caseSubtype'], $result['caseSubtype']);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_added()
    {
        $dataToMatch = [
            'reference_number' => $this->lpaUid,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $expectedException = new BadRequestException(
            'Lpa already added',
            [
                'donor'         => [
                    'uId'           => '12345',
                    'firstname'     => 'Example',
                    'middlenames'   => 'Donor',
                    'surname'       => 'Person',
                ],
                'caseSubtype' => 'hw',
                'lpaActorToken' => 'qwerty-54321'
            ]
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willThrow($expectedException);

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found()
    {
        $dataToMatch = [
            'reference_number' => $this->lpaUid,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

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

        $this->getSut()->validateRequest($this->userId, $dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_not_valid()
    {
        $lpa = new Lpa(
            [
                'uId'               => $this->lpaUid,
                'registrationDate'  => '2019-08-31',
                'status'            => 'Registered',
            ],
            new DateTime()
        );

        $dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Test Tester',
            'last_name'             => 'Testing',
            'postcode'              => 'Ab1 2Cd',
            'force_activation_key'  => false,
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData())
            ->willReturn(false);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $this->getSut()->validateRequest($this->userId, $dataToMatch);
    }

    /** @test */
    public function older_lpa_lookup_throws_an_exception_if_user_data_doesnt_match_lpa()
    {
        $dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Wrong Name',
            'last_name'             => 'Incorrect',
            'postcode'              => 'wR0 nG1',
            'force_activation_key'  => false,
        ];

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData())
            ->willReturn(true);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details do not match');

        $this->getSut()->validateRequest($this->userId, $dataToMatch);
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
            'uId'       => $this->actorUid,
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
                'registrationDate'  => '2021-01-01',
                'status'            => 'Registered',
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
}
