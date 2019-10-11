<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
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

    public function testGetLpa()
    {
        $this->markTestSkipped('must be revisited.');
        $this->apiClientProphecy->httpGet('/v1/lpa-by-code/123456789012')
            ->willReturn([
                'id'      => 123456789012,
                'isValid' => true,
                'another' => [
                    'some'  => 1,
                    'value' => 2,
                ],
            ]);

        $service = new LpaService($this->apiClientProphecy->reveal());

        $lpa = $service->getLpaByCode('1234-5678-9012');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(123456789012, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

    public function testGetLpaNotFound()
    {
        $this->markTestSkipped('must be revisited.');
        $this->apiClientProphecy->httpGet('/v1/lpa-by-code/123412341234')
            ->willReturn(null);

        $service = new LpaService($this->apiClientProphecy->reveal());

        $lpa = $service->getLpaByCode('1234-1234-1234');

        $this->assertNotInstanceOf(ArrayObject::class, $lpa);
        $this->assertNull($lpa);
    }

    public function testGetLpaById()
    {
        $this->markTestSkipped('must be revisited.');
        $this->apiClientProphecy->httpGet('/v1/lpa/123456789012')
            ->willReturn([
                'id'      => 123456789012,
                'isValid' => true,
                'another' => [
                    'some'  => 1,
                    'value' => 2,
                ],
            ]);

        $service = new LpaService($this->apiClientProphecy->reveal());

        $lpa = $service->getLpaById('123456789012');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(123456789012, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

    /** @test */
    public function it_finds_an_lpa_by_passcode()
    {
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

        $this->lpaFactoryProphecy->createLpaFromData($lpaData)->willReturn($lpa);

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaByPasscode($passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals(123456789012, $lpa->getUId());
        $this->assertEquals($donor, $lpa->getDonor());
        $this->assertEquals($dob, $lpa->getDonor()->getDob()->format('Y-m-d'));
    }

    /** @test */
    public function an_invalid_find_by_passcode_response_returns_null()
    {
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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpa = $service->getLpaByPasscode($passcode, $referenceNumber, $dob);

        $this->assertNull($lpa);
    }

    /** @test */
    public function it_confirms_an_lpa_by_passcode()
    {
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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpaCode = $service->confirmLpaAddition($passcode, $referenceNumber, $dob);

        $this->assertEquals('actor-lpa-code', $lpaCode);
    }

    /** @test */
    public function an_invalid_confirmation_of_lpa_by_passcode_returns_null()
    {
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

        $service = new LpaService($this->apiClientProphecy->reveal(), $this->lpaFactoryProphecy->reveal());

        $lpaCode = $service->confirmLpaAddition($passcode, $referenceNumber, $dob);

        $this->assertNull($lpaCode);
    }
}
