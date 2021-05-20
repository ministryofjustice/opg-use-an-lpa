<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use App\Service\Lpa\ValidateOlderLpaRequirements;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class OlderLpaServiceTest extends TestCase
{
    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|LpasInterface */
    private $lpasInterfaceProphecy;

    /** @var ObjectProphecy|UserLpaActorMapInterface */
    private $userLpaActorMapInterfaceProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|ActorCodes */
    public $actorCodesProphecy;

    /** @var ObjectProphecy|GetAttorneyStatus */
    private $getAttorneyStatusProphecy;

    /** @var ObjectProphecy|ValidateOlderLpaRequirements */
    private $validateOlderLpaRequirements;

    public function setUp()
    {
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->lpasInterfaceProphecy = $this->prophesize(LpasInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->validateOlderLpaRequirements = $this->prophesize(ValidateOlderLpaRequirements::class);
    }

    private function getOlderLpaService(): OlderLpaService
    {
        return new OlderLpaService(
            $this->lpaServiceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->actorCodesProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->validateOlderLpaRequirements->reveal()
        );
    }

    /** @test */
    public function request_access_code_letter(): void
    {
        $caseUid = '700000055554';
        $actorUid = '700000055554';

        $this->lpasInterfaceProphecy
            ->requestLetter((int) $caseUid, (int)$actorUid)
            ->shouldBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($caseUid, $actorUid);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails(): void
    {
        $caseUid = '700000055554';
        $actorUid = '700000055554';

        $this->lpasInterfaceProphecy
            ->requestLetter((int) $caseUid, (int)$actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);
        $service->requestAccessByLetter($caseUid, $actorUid);
    }

    /** @test */
    public function returns_code_created_date_if_code_exists_for_actor()
    {
        $actorUid = '700000055554';
        $lpaId = '700000012345';
        $createdDate = (new DateTime('now'))->modify('-15 days')->format('Y-m-d');

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => $createdDate
            ],
            new DateTime('now')
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeCreated = $service->hasActivationCode($lpaId, $actorUid);
        $this->assertEquals(DateTime::createFromFormat('Y-m-d', $createdDate), $codeCreated);
    }

    /** @test */
    public function returns_null_if_a_code_does_not_exist_for_an_actor()
    {
        $actorUid = '700000055554';
        $lpaId = '700000012345';

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => null
            ],
            new DateTime()
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeExists = $service->hasActivationCode($lpaId, $actorUid);
        $this->assertNull($codeExists);
    }

    /** @test */
    public function returns_data_in_correct_format_after_cleansing()
    {
        $data = [
            'dob'         => '1980-03-01',
            'first_names' => 'Test Tester',
            'last_name'   => 'Testing',
            'postcode'    => 'Ab1 2Cd'
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
            'dob'         => '1980-03-01',
            'first_names' => 'Test Tester',
            'last_name'   => 'Testing',
            'postcode'    => 'Ab1 2Cd'
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
     * @param array|null $expectedResponse
     * @param array $userData
     */
    public function returns_actor_and_lpa_id_if_match_found_in_lookup(?array $expectedResponse, array $userData)
    {
        $lpaId = '700000009999';

        $lpa = [
            'uId' => $lpaId,
            'donor' => [
                'uId' => '700000001111',
                'dob' => '1975-10-05',
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
                    'uId' => '700000002222',
                    'dob' => '1977-11-21',
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
                    'uId' => '700000003333',
                    'dob' => '1960-05-05',
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
                    'uId' => '700000001234',
                    'dob' => '1980-03-01',
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
            ->__invoke([
                'uId' => '700000002222',
                'dob' => '1977-11-21',
                'firstname' => 'Attorneyone',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'Gg1 2ff'
                    ]
                ],
                'systemStatus' => false, // inactive attorney
            ])
            ->willReturn(2);

        $this->getAttorneyStatusProphecy
            ->__invoke([
                'uId' => '700000003333',
                'dob' => '1960-05-05',
                'firstname' => '', // ghost attorney
                'surname'   => '',
                'addresses' => [
                    [
                        'postcode' => 'BB1 9ee'
                    ]
                ],
                'systemStatus' => true,
            ])
            ->willReturn(1);

        $this->getAttorneyStatusProphecy
            ->__invoke([
                'uId' => '700000001234',
                'dob' => '1980-03-01',
                'firstname' => 'Test',
                'surname'   => 'Testing',
                'addresses' => [
                    [
                        'postcode' => 'Ab1 2Cd'
                    ]
                ],
                'systemStatus' => true,
            ])
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
                    'actor-id' => '700000001234', // successful match for attorney
                    'lpa-id'   => '700000009999'
                ],
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                [
                    'actor-id' => '700000001111', // successful match for donor
                    'lpa-id'   => '700000009999'
                ],
                [
                    'dob'         => '1975-10-05',
                    'first_names' => 'Donor',
                    'last_name'   => 'Person',
                    'postcode'    => 'PY1 3Kd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1982-01-20', // dob will not match
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Wrong', // firstname will not match
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Incorrect', // surname will not match
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'WR0 NG1' // postcode will not match
                ],
            ],
            [
                null, // will not find a match as this attorney is inactive
                [
                    'dob'         => '1977-11-21',
                    'first_names' => 'Attorneyone',
                    'last_name'   => 'Person',
                    'postcode'    => 'Gg1 2ff'
                ],
            ],
            [
                null, // will not find a match as this attorney is a ghost
                [
                    'dob'         => '1960-05-05',
                    'first_names' => 'Attorneytwo',
                    'last_name'   => 'Person',
                    'postcode'    => 'BB1 9ee'
                ],
            ]
        ];
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found()
    {
        $lpaId = '700000004321';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_not_valid()
    {
        $lpaId = '700000004321';

        $lpa = new Lpa(
            [
                'uId' => $lpaId,
                'registrationDate' => '2019-08-31',
                'status' => 'Registered',
            ],
            new DateTime()
        );

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $this->lpaServiceProphecy
            ->getByUid($lpaId)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirements
            ->__invoke($lpa->getData())
            ->willReturn(false);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_user_data_doesnt_match_lpa()
    {
        $lpaId = '700000004321';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '1980-03-01',
            'first_names'      => 'Wrong Name',
            'last_name'        => 'Incorrect',
            'postcode'         => 'wR0 nG1'
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaServiceProphecy
            ->getByUid($lpaId)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirements
            ->__invoke($lpa->getData())
            ->willReturn(true);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details do not match');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws Exception
     */
    public function throws_exception_with_date_if_actor_has_active_activation_key()
    {
        $lpaId = '700000004321';
        $actorId = '700000004444';
        $createdDate = (new DateTime('-2 weeks'))->format('Y-m-d');

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaServiceProphecy
            ->getByUid($lpaId)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirements
            ->__invoke($lpa->getData())
            ->willReturn(true);

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorId)
            ->willReturn(new ActorCode(
                [
                    'Created' => $createdDate
                ],
                new DateTime()
            ));

        try {
            $service->checkLPAMatchAndGetActorDetails($dataToMatch);
        } catch (BadRequestException $ex) {
            $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            $this->assertEquals('LPA not eligible as an activation key already exists', $ex->getMessage());

            $this->assertEquals(
                [
                    'activation_key_created' => $createdDate,
                    'donor_name' => $lpa->getData()['donor']['firstname'] . " " . $lpa->getData()['donor']['middlenames'] . " " . $lpa->getData()['donor']['surname'],
                    'lpa_type' => $lpa->getData()['caseSubtype'],
                ],
                $ex->getAdditionalData()
            );
            return;
        }

        throw new ExpectationFailedException('Expected an activation key to already exist for actor');
    }

    /**
     * @test
     * @throws Exception
     */
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria()
    {
        $lpaId = '700000004321';
        $actorId = '700000004444';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '1980-03-01',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getOlderLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpaServiceProphecy
            ->getByUid($lpaId)
            ->willReturn($lpa);

        $this->validateOlderLpaRequirements
            ->__invoke($lpa->getData())
            ->willReturn(true);

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorId)
            ->willReturn(new ActorCode(
                [
                    'Created' => null
                ],
                new DateTime()
            ));

        $response = $service->checkLPAMatchAndGetActorDetails($dataToMatch);

        $this->assertEquals($actorId, $response['actor-id']);
        $this->assertEquals($lpaId, $response['lpa-id']);
    }

    /**
     * Returns the lpa data needed for checking in the older LPA journey
     *
     * @return Lpa
     */
    public function older_lpa_get_by_uid_response(): Lpa
    {
        $attorney1 = [
            'uId' => '700000002222',
            'dob' => '1977-11-21',
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
            'uId' => '700000004444',
            'dob' => '1980-03-01',
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
                'uId' => '700000004321',
                'registrationDate' => '2021-01-01',
                'status' => 'Registered',
                'caseSubtype' => 'pfa',
                'donor' => [
                    'uId' => '700000001111',
                    'dob' => '1975-10-05',
                    'firstname' => 'Donor',
                    'surname'   => 'Person',
                    'addresses' => [
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
