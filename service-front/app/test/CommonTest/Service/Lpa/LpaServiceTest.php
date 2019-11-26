<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Fig\Http\Message\StatusCodeInterface;
use ArrayObject;

class LpaServiceTest extends TestCase
{
    /**
     * @var Client
     */
    private $apiClientProphecy;

    /**
     * @var LpaFactory
     */
    private $lpaFactoryProphecy;

    public function setUp()
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);
        $this->lpaFactoryProphecy = $this->prophesize(LpaFactory::class);
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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willReturn([
                '0123-01-01-01-012345' => $lpaData

            ]);

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpaType);

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', false);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpa);

        $parsedLpa = $lpa['0123-01-01-01-012345'];
        $this->assertInstanceOf(ArrayObject::class, $parsedLpa);
        $this->assertEquals('other data', $parsedLpa->other);
        $this->assertInstanceOf(Lpa::class, $parsedLpa->lpa);
    }

    /** @test */
    public function it_gets_an_lpa_by_passcode_and_surname_for_full()
    {
        $lpaData = [
            'other' => 'other data',
            'lpa' => []
        ];

        $lpaType = new Lpa();

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/full', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willReturn([
                    '0123-01-01-01-012345' => $lpaData
            ]);

        $this->lpaFactoryProphecy->createLpaFromData($lpaData['lpa'])->willReturn($lpaType);

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', true);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpa);

        $parsedLpa = $lpa['0123-01-01-01-012345'];
        $this->assertInstanceOf(ArrayObject::class, $parsedLpa);
        $this->assertEquals('other data', $parsedLpa->other);
        $this->assertInstanceOf(Lpa::class, $parsedLpa->lpa);
    }

    /** @test */
    public function it_finds_an_expired_lpa_by_passcode_and_surname()
    {

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])->willThrow(new ApiException('',StatusCodeInterface::STATUS_GONE));

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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
        ])->willThrow(new ApiException('',StatusCodeInterface::STATUS_NOT_FOUND));

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaById($token, $lpaId);

        $this->assertInstanceOf(Lpa::class, $lpa);

    }

    /** @test */
    public function an_invalid_Lpa_id_throws_exception()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaId)
            ->willThrow(new ApiException('Error whilst making http GET request', 404));

        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals(123456789012, $lpa->getUId());
        $this->assertEquals($donor, $lpa->getDonor());
        $this->assertEquals($dob, $lpa->getDonor()->getDob()->format('Y-m-d'));
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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpaCode = $service->confirmLpaAddition($token, $passcode, $referenceNumber, $dob);

        $this->assertNull($lpaCode);
    }

}
