<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\ValidateOlderLpaRequirements;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class OlderLpaServiceTest extends TestCase
{
    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    /** @var ObjectProphecy|LpaAlreadyAdded */
    private $lpaAlreadyAddedProphecy;

    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|LpasInterface */
    private $lpasInterfaceProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|ActorCodes */
    public $actorCodesProphecy;

    /** @var ObjectProphecy|GetAttorneyStatus */
    private $getAttorneyStatusProphecy;

    /** @var ObjectProphecy|ValidateOlderLpaRequirements */
    private $validateOlderLpaRequirementsProphecy;

    /** @var UserLpaActorMapInterface|ObjectProphecy */
    private $userLpaActorMapProphecy;

    /** @var ObjectProphecy|ResolveActor */
    private $resolveActorProphecy;

    public string $userId;
    public string $lpaUid;
    public string $actorUid;
    public array $dataToMatch;

    public function setUp()
    {
        $this->lpaAlreadyAddedProphecy = $this->prophesize(LpaAlreadyAdded::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->lpasInterfaceProphecy = $this->prophesize(LpasInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->validateOlderLpaRequirementsProphecy = $this->prophesize(ValidateOlderLpaRequirements::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMap::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = '700000012345';
        $this->actorUid = '700000055554';

        $this->dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Test Tester',
            'last_name'             => 'Testing',
            'postcode'              => 'Ab1 2Cd',
            'force_activation_key'  => false,
        ];
    }

    private function getOlderLpaService(): OlderLpaService
    {
        return new OlderLpaService(
            $this->lpaAlreadyAddedProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->actorCodesProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->validateOlderLpaRequirementsProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    /** @test */
    public function request_access_code_letter(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_without_flag(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldNotBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function checks_if_lpa_already_added_and_throws_exception_if_yes()
    {
        $responseData = [
            'donor' => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'qwerty-54321',
        ];

        $expectedException = new BadRequestException('LPA already added', $responseData);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn($responseData);

        $this->expectExceptionObject($expectedException);

        $service = $this->getOlderLpaService();
        $service->checkIfLpaAlreadyAdded($this->userId, $this->lpaUid);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

        $this->userLpaActorMapProphecy->delete(Argument::type('string'))->willReturn([])->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails_without_flag(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string')
        )->shouldNotBeCalled();

        $this->userLpaActorMapProphecy->delete(Argument::type('string'))->willReturn([])->shouldNotBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function returns_code_created_date_if_code_exists_for_actor()
    {
        $createdDate = (new DateTime('now'))->modify('-15 days')->format('Y-m-d');

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => $createdDate
            ],
            new DateTime('now')
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeCreated = $service->hasActivationCode($this->lpaUid, $this->actorUid);
        $this->assertEquals(DateTime::createFromFormat('Y-m-d', $createdDate), $codeCreated);
    }

    /** @test */
    public function returns_null_if_a_code_does_not_exist_for_an_actor()
    {
        $lpaCodesResponse = new ActorCode(
            [
                'Created' => null
            ],
            new DateTime()
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeExists = $service->hasActivationCode($this->lpaUid, $this->actorUid);
        $this->assertNull($codeExists);
    }

    /** @test */
    public function returns_data_in_correct_format_after_cleansing()
    {
        $data = [
            'dob'           => '1980-03-01',
            'first_names'   => 'Test Tester',
            'last_name'     => 'Testing',
            'postcode'      => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $cleansedData = $service->cleanseUserData($data);
        $this->assertEquals('test', $cleansedData['first_names']);
        $this->assertEquals('testing', $cleansedData['last_name']);
        $this->assertEquals('ab12cd', $cleansedData['postcode']);
    }

    /** @test */
    public function returns_the_actor_if_user_data_matches_the_actor_data()
    {
        $actor = [
            'dob'       => '1980-03-01',
            'firstname' => 'Test',
            'surname'   => 'Testing',
            'addresses' => [
                ['postcode' => 'Ab1 2Cd']
            ]
        ];

        $userData = [
            'dob'           => '1980-03-01',
            'first_names'   => 'Test Tester',
            'last_name'     => 'Testing',
            'postcode'      => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $userData = $service->cleanseUserData($userData);

        $actorMatch = $service->checkDataMatch($actor, $userData);
        $this->assertEquals($actor, $actorMatch);
    }

    /** @test */
    public function returns_null_if_actor_has_more_than_one_address()
    {
        $actor = [
            'addresses' => [
                ['postcode' => 'ab1 2cd'],
                ['postcode' => 'gw1 9hp']
            ]
        ];

        $service = $this->getOlderLpaService();

        $dataMatch = $service->checkDataMatch($actor, []);
        $this->assertNull($dataMatch);
    }

    /**
     * @test
     * @dataProvider actorLookupDataProvider
     *
     * @param array|null $expectedResponse
     * @param array      $userData
     */
    public function returns_actor_and_lpa_details_if_match_found_in_lookup(?array $expectedResponse, array $userData)
    {
        $lpa = [
            'uId'   => '700000012345',
            'donor' => [
                'uId'       => '700000001111',
                'dob'       => '1975-10-05',
                'firstname' => 'Donor',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'PY1 3Kd'
                    ]
                ]
            ],
            'attorneys' => [
                [
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
                ],
                [
                    'uId'       => '700000003333',
                    'dob'       => '1960-05-05',
                    'firstname' => '', // ghost attorney
                    'surname'   => '',
                    'addresses' => [
                        [
                            'postcode' => 'BB1 9ee'
                        ]
                    ],
                    'systemStatus' => true,
                ],
                [
                    'uId'       => '700000001234',
                    'dob'       => '1980-03-01',
                    'firstname' => 'Test',
                    'surname'   => 'Testing',
                    'addresses' => [
                        [
                            'postcode' => 'Ab1 2Cd'
                        ]
                    ],
                    'systemStatus' => true,
                ]
            ]
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke(
                [
                    'uId'       => '700000002222',
                    'dob'       => '1977-11-21',
                    'firstname' => 'Attorneyone',
                    'surname'   => 'Person',
                    'addresses' => [
                        [
                            'postcode' => 'Gg1 2ff'
                        ]
                    ],
                    'systemStatus' => false, // inactive attorney
                ]
            )
            ->willReturn(2);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                [
                    'uId'       => '700000003333',
                    'dob'       => '1960-05-05',
                    'firstname' => '', // ghost attorney
                    'surname'   => '',
                    'addresses' => [
                        [
                            'postcode' => 'BB1 9ee'
                        ]
                    ],
                    'systemStatus' => true,
                ]
            )
            ->willReturn(1);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                [
                    'uId'       => '700000001234',
                    'dob'       => '1980-03-01',
                    'firstname' => 'Test',
                    'surname'   => 'Testing',
                    'addresses' => [
                        [
                            'postcode' => 'Ab1 2Cd'
                        ]
                    ],
                    'systemStatus' => true,
                ]
            )
            ->willReturn(0); // active attorney

        $service = $this->getOlderLpaService();

        $userData = $service->cleanseUserData($userData);

        $actorMatch = $service->compareAndLookupActiveActorInLpa($lpa, $userData);
        $this->assertEquals($expectedResponse, $actorMatch);
    }

    public function actorLookupDataProvider(): array
    {
        return [
            [
                [
                    'actor-id'  => '700000001234', // successful match for attorney
                    'lpa-id'    => '700000012345',
                ],
                [
                    'dob'           => '1980-03-01',
                    'first_names'   => 'Test Tester',
                    'last_name'     => 'Testing',
                    'postcode'      => 'Ab1 2Cd'
                ],
            ],
            [
                [
                    'actor-id'  => '700000001111', // successful match for donor
                    'lpa-id'    => '700000012345',
                ],
                [
                    'dob'           => '1975-10-05',
                    'first_names'   => 'Donor',
                    'last_name'     => 'Person',
                    'postcode'      => 'PY1 3Kd'
                ],
            ],
            [
                null,
                [
                    'dob'           => '1982-01-20', // dob will not match
                    'first_names'   => 'Test Tester',
                    'last_name'     => 'Testing',
                    'postcode'      => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'           => '1980-03-01',
                    'first_names'   => 'Wrong', // firstname will not match
                    'last_name'     => 'Testing',
                    'postcode'      => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'           => '1980-03-01',
                    'first_names'   => 'Test Tester',
                    'last_name'     => 'Incorrect', // surname will not match
                    'postcode'      => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'           => '1980-03-01',
                    'first_names'   => 'Test Tester',
                    'last_name'     => 'Testing',
                    'postcode'      => 'WR0 NG1' // postcode will not match
                ],
            ],
            [
                null, // will not find a match as this attorney is inactive
                [
                    'dob'           => '1977-11-21',
                    'first_names'   => 'Attorneyone',
                    'last_name'     => 'Person',
                    'postcode'      => 'Gg1 2ff'
                ],
            ],
            [
                null, // will not find a match as this attorney is a ghost
                [
                    'dob'           => '1960-05-05',
                    'first_names'   => 'Attorneytwo',
                    'last_name'     => 'Person',
                    'postcode'      => 'BB1 9ee'
                ],
            ]
        ];
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_added()
    {
        $service = $this->getOlderLpaService();

        $expectedException = new BadRequestException(
            'Lpa already added',
            [
                'donor' => [
                    'uId'           => '12345',
                    'firstname'     => 'Example',
                    'middlenames'   => 'Donor',
                    'surname'       => 'Person',
                ],
                'caseSubtype'   => 'hw',
                'lpaActorToken' => 'qwerty-54321'
            ]
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willThrow($expectedException);

        $this->expectExceptionObject($expectedException);

        $service->checkLPAMatchAndGetActorDetails($this->userId, $this->dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found()
    {
        $service = $this->getOlderLpaService();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpasInterfaceProphecy
            ->get($this->lpaUid)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $service->checkLPAMatchAndGetActorDetails($this->userId, $this->dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
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

        $service = $this->getOlderLpaService();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData())
            ->willThrow(new BadRequestException('LPA not eligible due to registration date'));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $service->checkLPAMatchAndGetActorDetails($this->userId, $this->dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
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

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details do not match');

        $service->checkLPAMatchAndGetActorDetails($this->userId, $dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function allow_user_to_continue_if_actor_has_active_activation_key()
    {
        $createdDate = (new DateTime('-2 weeks'))->format('Y-m-d');

        $responseData = [
            'donor' => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn(
                new ActorCode(
                    [
                        'Created' => $createdDate,
                    ],
                    new DateTime()
                )
            );

        $expectedException = new BadRequestException('LPA has an activation key already', $responseData);
        $this->expectExceptionObject($expectedException);
        $service->validateOlderLpaRequest($this->userId, $this->dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function allow_user_continue_to_generate_new_activation_key_even_if_actor_has_active_activation_key()
    {
        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn(
                new ActorCode(
                    [
                        'Created' => null,
                    ],
                    new DateTime()
                )
            );

        $result = $service->validateOlderLpaRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($this->actorUid, $result['actor-id']);
        $this->assertEquals($this->lpaUid, $result['lpa-id']);
        $this->assertEquals($lpa->getData()['donor']['uId'], $result['donor']['uId']);
        $this->assertEquals($lpa->getData()['donor']['firstname'], $result['donor']['firstname']);
        $this->assertEquals($lpa->getData()['donor']['middlenames'], $result['donor']['middlenames']);
        $this->assertEquals($lpa->getData()['donor']['surname'], $result['donor']['surname']);
        $this->assertEquals($lpa->getData()['caseSubtype'], $result['caseSubtype']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria()
    {
        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn(
                new ActorCode(
                    [
                        'Created' => null
                    ],
                    new DateTime()
                )
            );

        $result = $service->checkLPAMatchAndGetActorDetails($this->userId, $this->dataToMatch);

        $this->assertEquals($this->actorUid, $result['actor-id']);
        $this->assertEquals($this->lpaUid, $result['lpa-id']);
        $this->assertEquals($lpa->getData()['donor']['uId'], $result['donor']['uId']);
        $this->assertEquals($lpa->getData()['donor']['firstname'], $result['donor']['firstname']);
        $this->assertEquals($lpa->getData()['donor']['middlenames'], $result['donor']['middlenames']);
        $this->assertEquals($lpa->getData()['donor']['surname'], $result['donor']['surname']);
        $this->assertEquals($lpa->getData()['caseSubtype'], $result['caseSubtype']);
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

        $this->getAttorneyStatusProphecy
            ->__invoke($attorney1)
            ->willReturn(1);

        $this->getAttorneyStatusProphecy
            ->__invoke($attorney2)
            ->willReturn(0);

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

    /**
     * @test
     */
    public function thows_exception_when_lpa_match_found_but_activation_key_already_exists()
    {
        $createdDate = (new DateTime('-2 weeks'))->format('Y-m-d');

        $lpaMatchResponse = [
            'caseSubtype' => 'pfa',
            'donor' => [
                'uId'           => '700000001111',
                'firstname'     => 'Donor',
                'middlenames'   => 'Example',
                'surname'       => 'Person'
            ],
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn(
                new ActorCode(
                    [
                        'Created' => $createdDate,
                    ],
                    new DateTime()
                )
            );

        try {
            $service->validateOlderLpaRequest($this->userId, $this->dataToMatch);
        } catch (BadRequestException $ex) {
            $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            $this->assertEquals('LPA has an activation key already', $ex->getMessage());
            $this->assertEquals($lpaMatchResponse, $ex->getAdditionalData());
        }
    }

    /**
     * @test
     */
    public function older_lpa_request_is_saved_with_a_TTL()
    {
        $this->userLpaActorMapProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();

        $service->storeLPARequest($this->lpaUid, $this->userId, $this->actorUid);
    }

    /**
     * @test
     */
    public function older_lpa_request_is_looped_until_no_id_collision()
    {
        $createCalls = 0;
        $this->userLpaActorMapProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->will(
            function () use (&$createCalls) {
                if ($createCalls > 0) {
                    return;
                }

                $createCalls++;
                throw new KeyCollisionException();
            }
        );

        $service = $this->getOlderLpaService();

        $service->storeLPARequest($this->lpaUid, $this->userId, $this->actorUid);
    }

    /**
     * @test
     */
    public function allow_user_to_continue_request_for_activation_key_if_lpa_match_found()
    {
        $lpaMatchResponse = [
            'actor-id'          => '700000055554',
            'lpa-id'            => '700000012345',
            'attorney' => [
                'uId'           => null,
                'firstname'     => null,
                'middlenames'   => null,
                'surname'       => null
            ],
            'caseSubtype' => 'pfa',
            'donor' => [
                'uId'         => '700000001111',
                'firstname'   => 'Donor',
                'middlenames' => 'Example',
                'surname'     => 'Person'
            ],
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData());

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn(
                new ActorCode(
                    [
                        'Created' => null,
                    ],
                    new DateTime()
                )
            );

        $response = $service->validateOlderLpaRequest($this->userId, $this->dataToMatch);
        $this->assertIsArray($response);

        $this->assertEquals($lpaMatchResponse, $response);
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_status_not_valid()
    {
        $lpa = new Lpa(
            [
                'uId'               => $this->lpaUid,
                'registrationDate'  => '2019-10-31',
                'status'            => 'Pending',
            ],
            new DateTime()
        );

        $service = $this->getOlderLpaService();

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->lpaServiceProphecy
            ->getByUid($this->lpaUid)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirementsProphecy
            ->__invoke($lpa->getData())
            ->willThrow(new NotFoundException('LPA status invalid'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA status invalid');

        $service->checkLPAMatchAndGetActorDetails($this->userId, $this->dataToMatch);
    }
}
