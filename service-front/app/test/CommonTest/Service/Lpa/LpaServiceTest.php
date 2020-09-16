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

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', LpaService::SUMMARY);

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
            ]
        )->willReturn($lpaData);

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpaType);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', LpaService::FULL);

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


        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', false);
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


        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', false);
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

        $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', false);
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

    /** @test */
    public function can_sort_lpas_by_donors_surname()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $lpaData1 = [
            'lpa' => [
                'uId' => $referenceNumber,
                'donor' => [
                    'uId' => $referenceNumber,
                    'dob' => $dob,
                    'surname' => 'Williams'
                ]
            ]
        ];

        $lpaData2 = [
            'lpa' => [
                'uId' => $referenceNumber,
                'donor' => [
                    'uId' => $referenceNumber,
                    'dob' => $dob,
                    'surname' => 'Johnson'
                ]
            ]
        ];

        $lpaData3 = [
            'lpa' => [
                'uId' => $referenceNumber,
                'donor' => [
                    'uId' => $referenceNumber,
                    'dob' => $dob,
                    'surname' => 'Taylor'
                ]
            ]
        ];

        $lpa1 = new Lpa();
        $lpa1->setUId($referenceNumber);
        $donor1 = new CaseActor();
        $donor1->setUId($referenceNumber);
        $donor1->setDob(new \DateTime($dob));
        $donor1->setSurname('Williams');
        $lpa1->setDonor($donor1);

        $lpa2 = new Lpa();
        $lpa2->setUId($referenceNumber);
        $donor2 = new CaseActor();
        $donor2->setUId($referenceNumber);
        $donor2->setDob(new \DateTime($dob));
        $donor2->setSurname('Johnson');
        $lpa2->setDonor($donor2);

        $lpa3 = new Lpa();
        $lpa3->setUId($referenceNumber);
        $donor3 = new CaseActor();
        $donor3->setUId($referenceNumber);
        $donor3->setDob(new \DateTime($dob));
        $donor3->setSurname('Taylor');
        $lpa3->setDonor($donor3);

        $this->apiClientProphecy->httpGet('/v1/lpas')
            ->willReturn([
                '0123-01-01-01-012345' => $lpaData1, // UserLpaActorMap from DynamoDb
                '9876-01-01-01-012345' => $lpaData2,
                '3456-01-01-01-012345' => $lpaData3
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->lpaFactoryProphecy->createLpaFromData($lpaData1['lpa'])->willReturn($lpa1);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData2['lpa'])->willReturn($lpa2);
        $this->lpaFactoryProphecy->createLpaFromData($lpaData3['lpa'])->willReturn($lpa3);

        $service = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->lpaFactoryProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $lpas = $service->getLpas($token);

        $orderedLpas = $service->sortLpasByDonorSurname($lpas);

        $resultOrder = [];
        foreach ($orderedLpas as $lpaKey => $lpaData) {
            $surname = $lpaData['lpa']->getDonor()->getSurname();
            array_push($resultOrder, $surname);
        }

        $this->assertEquals('Johnson', $resultOrder[0]);
        $this->assertEquals('Taylor', $resultOrder[1]);
        $this->assertEquals('Williams', $resultOrder[2]);
    }
}
