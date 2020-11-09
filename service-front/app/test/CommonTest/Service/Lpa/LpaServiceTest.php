<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LpaServiceTest extends TestCase
{
    /**
     * @var Client
     */
    private $apiClientProphecy;

    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var LpaFactory
     */
    private $lpaFactoryProphecy;

    public function setUp()
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);
        $this->lpaFactoryProphecy = $this->prophesize(LpaFactory::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function it_gets_a_list_of_lpas_for_a_user()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $lpaData = [
            'other' => 'other data',
            'lpa' => [
                'uId' => $referenceNumber,
                'donor' => [
                    'uId' => $referenceNumber,
                    'dob' => $dob
                ]
            ]
        ];

        $lpa = new Lpa();
        $lpa->setUId($referenceNumber);

        $donor = new CaseActor();
        $donor->setUId($referenceNumber);
        $donor->setDob(new \DateTime($dob));
        $lpa->setDonor($donor);

        $this->apiClientProphecy->httpGet('/v1/lpas')
            ->willReturn([
                '0123-01-01-01-012345' => $lpaData // UserLpaActorMap from DynamoDb
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpa);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpas = $service->getLpas($token);

        $this->assertInstanceOf(ArrayObject::class, $lpas);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpas);

        $parsedLpa = $lpas['0123-01-01-01-012345'];
        $this->assertInstanceOf(ArrayObject::class, $parsedLpa);
        $this->assertEquals('other data', $parsedLpa->other);
        $this->assertInstanceOf(Lpa::class, $parsedLpa->lpa);
    }

    /** @test */
    public function it_gets_an_lpa_by_passcode_and_surname_for_summary()
    {
        $lpaData = [
            'other' => 'other data',
            'lpa' => []
        ];

        $lpaType = new Lpa();

        $this->apiClientProphecy->httpPost(
            '/v1/viewer-codes/summary',
            [
                'code' => 'P9H8A6MLD3AM',
                'name' => 'Sanderson',
            ]
        )->willReturn($lpaData);

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpaType);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);

        $this->assertInstanceOf(ArrayObject::class, $lpa);

        $this->assertEquals('other data', $lpa->other);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    /** @test */
    public function it_gets_an_lpa_by_passcode_and_surname_for_full()
    {
        $lpaData = [
            'other' => 'other data',
            'lpa' => []
        ];

        $lpaType = new Lpa();

        $this->apiClientProphecy->httpPost(
            '/v1/viewer-codes/full',
            [
                'code' => 'P9H8A6MLD3AM',
                'name' => 'Sanderson',
                'organisation' => 'Santander'
            ]
        )->willReturn($lpaData);

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpaType);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', 'Santander');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals('other data', $lpa->other);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }


    /** @test */
    public function it_finds_a_cancelled_share_code_by_passcode_and_surname()
    {

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrow(new ApiException('Share code cancelled', StatusCodeInterface::STATUS_GONE));

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);
        $this->expectExceptionMessage('Share code cancelled');


        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }
    /** @test */
    public function it_finds_an_expired_share_code_by_passcode_and_surname()
    {

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrow(new ApiException('Share code expired', StatusCodeInterface::STATUS_GONE));

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);


        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    /** @test */
    public function lpa_not_found_by_passcode_and_surname()
    {
        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])->willThrow(new ApiException('', StatusCodeInterface::STATUS_NOT_FOUND));

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    /** @test */
    public function it_gets_an_Lpa_by_Id()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $lpaData = [
            'user-lpa-actor-token' => $lpaId,
            'lpa' => [
                'id' => '70000000047'
            ]
        ];

        $lpa = new Lpa();

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaId)
            ->willReturn([
                'lpa' => $lpaData['lpa'],
                'lpaId' => $lpaData['user-lpa-actor-token']
            ]);

        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpa);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaById($token, $lpaId);

        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    /** @test */
    public function an_invalid_Lpa_id_throws_exception()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaId)
            ->willThrow(new ApiException('Error whilst making http GET request', 404));

        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);

        $service->getLpaById($token, $lpaId);
    }

    /** @test */
    public function it_finds_an_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $lpaData = [
            'uId' => $referenceNumber,
            'donor' => [
                'dob' => $dob
            ]
        ];

        $lpa = new Lpa();
        $lpa->setUId($referenceNumber);

        $donor = new CaseActor();
        $donor->setDob(new \DateTime($dob));
        $lpa->setDonor($donor);

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([
                'lpa' => $lpaData
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData)->willReturn($lpa);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa['lpa']);
        $this->assertEquals(123456789012, ($lpa['lpa'])->getUId());
        $this->assertEquals($donor, ($lpa['lpa'])->getDonor());
        $this->assertEquals($dob, ($lpa['lpa'])->getDonor()->getDob()->format('Y-m-d'));
    }


    /** @test */
    public function it_finds_a_cancelled_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';
        $cancellationDate = (new \DateTime('-1 days'))->format('Y-m-d');

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $lpaData = [
            'uId' => $referenceNumber,
            'cancellationDate' => $cancellationDate,
            'donor' => [
                'dob' => $dob
            ]
        ];

        $lpa = new Lpa();
        $lpa->setUId($referenceNumber);

        $donor = new CaseActor();
        $donor->setDob(new \DateTime($dob));
        $lpa->setDonor($donor);

        $lpa->setCancellationDate(new \DateTime($cancellationDate));

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([
                'lpa' => $lpaData
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData)->willReturn($lpa);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa['lpa']);
        $this->assertEquals(123456789012, ($lpa['lpa'])->getUId());
        $this->assertEquals($donor, ($lpa['lpa'])->getDonor());
        $this->assertEquals($dob, ($lpa['lpa'])->getDonor()->getDob()->format('Y-m-d'));
        $this->assertEquals($cancellationDate, ($lpa['lpa'])->getCancellationDate()->format('Y-m-d'));
    }

    /** @test */
    public function an_invalid_find_by_passcode_response_returns_null()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([ 'bad-response' => 'bad']);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertNull($lpa);
    }

    /** @test */
    public function it_confirms_an_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/confirm', $params)
            ->willReturn([
                'user-lpa-actor-token' => 'actor-lpa-code'
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpaCode = $service->confirmLpaAddition($token, $passcode, $referenceNumber, $dob);

        $this->assertEquals('actor-lpa-code', $lpaCode);
    }

    /** @test */
    public function an_invalid_confirmation_of_lpa_by_passcode_returns_null()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/confirm', $params)
            ->willReturn([
                'bad_response' => 'bad'
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpaCode = $service->confirmLpaAddition($token, $passcode, $referenceNumber, $dob);

        $this->assertNull($lpaCode);
    }

    public function get_test_lpa_data_for_sorting_unit_tests()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $dob = '1980-01-01';

        // --------------------- Daniel Williams, 3 LPAs, 1 HW, 2 PFA

        $lpaData1 = [
            'lpa' => [
                'uId' => '700000000001',
                'caseSubtype' => 'hw',
                'donor' => [
                    'uId' => '700000000001',
                    'dob' => $dob,
                    'firstname' => 'Daniel',
                    'surname' => 'Williams'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-1 hour')) // added an hour ago
        ];

        $lpaData5 = [
            'lpa' => [
                'uId' => '700000000005',
                'caseSubtype' => 'pfa',
                'donor' => [
                    'uId' => '700000000001',
                    'dob' => $dob,
                    'firstname' => 'Daniel',
                    'surname' => 'Williams'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ];

        $lpaData6 = [
            'lpa' => [
                'uId' => '700000000006',
                'caseSubtype' => 'pfa',
                'donor' => [
                    'uId' => '700000000001',
                    'dob' => $dob,
                    'firstname' => 'Daniel',
                    'surname' => 'Williams'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ];

        // --------------------- Amy Johnson, 2 LPAs, both PFA

        $lpaData2 = [
            'lpa' => [
                'uId' => '700000000002',
                'caseSubtype' => 'pfa',
                'donor' => [
                    'uId' => '700000000002',
                    'dob' => $dob,
                    'firstname' => 'Amy',
                    'surname' => 'Johnson'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ];

        $lpaData7 = [
            'lpa' => [
                'uId' => '700000000007',
                'caseSubtype' => 'pfa',
                'donor' => [
                    'uId' => '700000000002',
                    'dob' => $dob,
                    'firstname' => 'Amy',
                    'surname' => 'Johnson'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
        ];

        // --------------------- Sam Taylor, 2 LPAs, both HW

        $lpaData3 = [
            'lpa' => [
                'uId' => '700000000003',
                'caseSubtype' => 'hw',
                'donor' => [
                    'uId' => '700000000003',
                    'dob' => $dob,
                    'firstname' => 'Sam',
                    'surname' => 'Taylor'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ];

        $lpaData4 = [
            'lpa' => [
                'uId' => '700000000004',
                'caseSubtype' => 'hw',
                'donor' => [
                    'uId' => '700000000004',
                    'dob' => $dob,
                    'firstname' => 'Sam',
                    'surname' => 'Taylor'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ];

        // --------------------- Gemma Taylor, 1 HW LPA

        $lpaData8 = [
            'lpa' => [
                'uId' => '700000000008',
                'caseSubtype' => 'hw',
                'donor' => [
                    'uId' => '700000000008',
                    'dob' => $dob,
                    'firstname' => 'Gemma',
                    'surname' => 'Taylor'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ];

        $lpaData9 = [
            'lpa' => [
                'uId' => '700000000009',
                'caseSubtype' => 'hw',
                'donor' => [
                    'uId' => '700000000009',
                    'dob' => '1998-02-09', // different donor with different dob so should not be grouped
                    'firstname' => 'Gemma',
                    'surname' => 'Taylor'
                ]
            ],
            'added' => date('Y-m-d H:i:s', strtotime('-9 hours'))
        ];

        // ---- Daniel Williams 3 LPAs

        $lpa1 = new Lpa();
        $lpa1->setUId('700000000001');
        $lpa1->setCaseSubtype('hw');
        $donor1 = new CaseActor();
        $donor1->setUId('700000000001');
        $donor1->setDob(new \DateTime($dob));
        $donor1->setFirstname('Daniel');
        $donor1->setSurname('Williams');
        $lpa1->setDonor($donor1);

        $lpa5 = new Lpa();
        $lpa5->setUId('700000000005');
        $lpa5->setCaseSubtype('pfa');
        $lpa5->setDonor($donor1);

        $lpa6 = new Lpa();
        $lpa6->setUId('700000000006');
        $lpa6->setCaseSubtype('pfa');
        $lpa6->setDonor($donor1);

        // ---- Amy Johnson 2 LPAs

        $lpa2 = new Lpa();
        $lpa2->setUId('700000000002');
        $lpa2->setCaseSubtype('pfa');
        $donor2 = new CaseActor();
        $donor2->setUId('700000000002');
        $donor2->setDob(new \DateTime($dob));
        $donor2->setFirstname('Amy');
        $donor2->setSurname('Johnson');
        $lpa2->setDonor($donor2);

        $lpa7 = new Lpa();
        $lpa7->setUId('700000000007');
        $lpa7->setCaseSubtype('pfa');
        $lpa7->setDonor($donor2);

        // ---- Sam Taylor 2 LPAs

        $lpa3 = new Lpa();
        $lpa3->setUId('700000000003');
        $lpa3->setCaseSubtype('hw');
        $donor3 = new CaseActor();
        $donor3->setUId('700000000003');
        $donor3->setDob(new \DateTime($dob));
        $donor3->setFirstname('Sam');
        $donor3->setSurname('Taylor');
        $lpa3->setDonor($donor3);

        $lpa4 = new Lpa();
        $lpa4->setUId('700000000004');
        $lpa4->setCaseSubtype('hw');
        $lpa4->setDonor($donor3);

        // ---- Gemma Taylor 1 LPA (to test case if surname is same, donors are then ordered by firstname)

        $lpa8 = new Lpa();
        $lpa8->setUId('700000000008');
        $lpa8->setCaseSubtype('hw');
        $donor8 = new CaseActor();
        $donor8->setUId('700000000008');
        $donor8->setDob(new \DateTime($dob));
        $donor8->setFirstname('Gemma');
        $donor8->setSurname('Taylor');
        $lpa8->setDonor($donor8);

        // ---- Different donor! Gemma Taylor 1 LPA (to test case if donors with same name but different dob arent grouped)

        $lpa9 = new Lpa();
        $lpa9->setUId('700000000009');
        $lpa9->setCaseSubtype('hw');
        $donor9 = new CaseActor();
        $donor9->setUId('700000000009');
        $donor9->setDob(new \DateTime('1998-02-09'));
        $donor9->setFirstname('Gemma');
        $donor9->setSurname('Taylor');
        $lpa9->setDonor($donor9);


        $this->apiClientProphecy->httpGet('/v1/lpas')
            ->willReturn([
                '0001-01-01-01-111111' => $lpaData1, // UserLpaActorMap from DynamoDb
                '0002-01-01-01-222222' => $lpaData2,
                '0003-01-01-01-333333' => $lpaData3,
                '0004-01-01-01-444444' => $lpaData4,
                '0005-01-01-01-555555' => $lpaData5,
                '0006-01-01-01-666666' => $lpaData6,
                '0007-01-01-01-777777' => $lpaData7,
                '0008-01-01-01-888888' => $lpaData8,
                '0009-01-01-01-999999' => $lpaData9
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData1['lpa'])->willReturn($lpa1);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData2['lpa'])->willReturn($lpa2);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData3['lpa'])->willReturn($lpa3);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData4['lpa'])->willReturn($lpa4);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData5['lpa'])->willReturn($lpa5);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData6['lpa'])->willReturn($lpa6);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData7['lpa'])->willReturn($lpa7);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData8['lpa'])->willReturn($lpa8);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData9['lpa'])->willReturn($lpa9);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpas = $service->getLpas($token);

        return $lpas;
    }

    /** @test */
    public function can_sort_lpas_into_final_order()
    {
        $lpas = $this->get_test_lpa_data_for_sorting_unit_tests();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $completeOrder = $service->sortLpasInOrder($lpas);

        $completeOrder = $completeOrder->getArrayCopy();

        $this->assertEquals(['0007-01-01-01-777777', '0002-01-01-01-222222'], array_keys($completeOrder['Amy Johnson 1980-01-01']));
        $this->assertEquals(['0008-01-01-01-888888'], array_keys($completeOrder['Gemma Taylor 1980-01-01']));
        $this->assertEquals(['0009-01-01-01-999999'], array_keys($completeOrder['Gemma Taylor 1998-02-09']));
        $this->assertEquals(['0004-01-01-01-444444', '0003-01-01-01-333333'], array_keys($completeOrder['Sam Taylor 1980-01-01']));
        $this->assertEquals(['0001-01-01-01-111111', '0006-01-01-01-666666', '0005-01-01-01-555555'], array_keys($completeOrder['Daniel Williams 1980-01-01']));
    }

    /** @test */
    public function can_sort_lpas_by_donors_surname()
    {
        $lpas = $this->get_test_lpa_data_for_sorting_unit_tests();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $orderedLpas = $service->sortLpasByDonorSurname($lpas);

        $resultOrder = [];
        foreach ($orderedLpas as $lpaKey => $lpaData) {
            $name = $lpaData['lpa']->getDonor()->getFirstname() . " " . $lpaData['lpa']->getDonor()->getSurname();
            array_push($resultOrder, $name);
        }

        $this->assertEquals('Amy Johnson', $resultOrder[0]);
        $this->assertEquals('Amy Johnson', $resultOrder[1]);
        $this->assertEquals('Gemma Taylor', $resultOrder[2]);
        $this->assertEquals('Gemma Taylor', $resultOrder[3]);
        $this->assertEquals('Sam Taylor', $resultOrder[4]);
        $this->assertEquals('Sam Taylor', $resultOrder[5]);
        $this->assertEquals('Daniel Williams', $resultOrder[6]);
        $this->assertEquals('Daniel Williams', $resultOrder[7]);
        $this->assertEquals('Daniel Williams', $resultOrder[8]);

        return $orderedLpas;
    }

    /** @test */
    public function can_group_lpas_by_donor()
    {
        $lpas = $this->can_sort_lpas_by_donors_surname();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $groupedLpas = $service->groupLpasByDonor($lpas);

        $groupedLpasArray = $groupedLpas->getArrayCopy();

        $groupedLpaKeys = array_keys($groupedLpasArray);

        $this->assertEquals('Amy Johnson 1980-01-01', $groupedLpaKeys[0]);
        $this->assertEquals('Gemma Taylor 1980-01-01', $groupedLpaKeys[1]);
        $this->assertEquals('Gemma Taylor 1998-02-09', $groupedLpaKeys[2]);
        $this->assertEquals('Sam Taylor 1980-01-01', $groupedLpaKeys[3]);
        $this->assertEquals('Daniel Williams 1980-01-01', $groupedLpaKeys[4]);
        $this->assertEquals(2, sizeof($groupedLpasArray['Amy Johnson 1980-01-01']));
        $this->assertEquals(1, sizeof($groupedLpasArray['Gemma Taylor 1980-01-01']));
        $this->assertEquals(1, sizeof($groupedLpasArray['Gemma Taylor 1998-02-09']));
        $this->assertEquals(2, sizeof($groupedLpasArray['Sam Taylor 1980-01-01']));
        $this->assertEquals(3, sizeof($groupedLpasArray['Daniel Williams 1980-01-01']));

        return $groupedLpas;
    }

    /** @test */
    public function can_order_lpas_by_type_followed_by_most_recently_added()
    {
        $lpasGroupedByDonor = $this->can_group_lpas_by_donor();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $orderedLpas = $service->sortGroupedDonorsLpasByTypeThenAddedDate($lpasGroupedByDonor);

        $orderedLpasArray = $orderedLpas->getArrayCopy();

        $this->assertEquals(['0007-01-01-01-777777', '0002-01-01-01-222222'], array_keys($orderedLpasArray['Amy Johnson 1980-01-01']));
        $this->assertEquals(['0004-01-01-01-444444', '0003-01-01-01-333333'], array_keys($orderedLpasArray['Sam Taylor 1980-01-01']));
        $this->assertEquals(['0008-01-01-01-888888'], array_keys($orderedLpasArray['Gemma Taylor 1980-01-01']));
        $this->assertEquals(['0009-01-01-01-999999'], array_keys($orderedLpasArray['Gemma Taylor 1998-02-09']));
        $this->assertEquals(['0001-01-01-01-111111', '0006-01-01-01-666666', '0005-01-01-01-555555'], array_keys($orderedLpasArray['Daniel Williams 1980-01-01']));
    }

    /** @test */
    public function it_confirms_remove_lpa()
    {
        $userToken = '01234567-01234-01234-01234-012345678901';
        $lpaActorToken = '98765432-01234-01234-01234-012345678901';

        $lpaData = [
            'user-lpa-actor-token' => $lpaActorToken,
            'lpa' => [
                'id' => '70000000047'
            ]
        ];

        $lpa = new Lpa();
        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaActorToken)
            ->willReturn([
                'lpa' => $lpaData['lpa'],
                'lpaId' => $lpaData['user-lpa-actor-token']
            ]);

        $this->apiClientProphecy->setUserTokenHeader($userToken)->shouldBeCalled();

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpaActordata = $service->removeLpa($userToken, $lpaActorToken);

        $this->assertEmpty($lpaActordata);
        $this->assertNotEmpty($lpaActorToken, $lpaActordata['lpaActorToken']);
    }
}
