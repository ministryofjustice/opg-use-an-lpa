<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Service\ApiClient\Client;
use Common\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use ArrayObject;

class LpaServiceTest extends TestCase
{
    /**
     * Client
     */
    private $apiClientProphecy;

    public function setUp()
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);
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

    public function testSearch()
    {
        $this->markTestSkipped('must be revisited.');
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpGet('/v1/lpa-search', $params)
            ->willReturn([
                'id'      => 123456789012,
                'isValid' => true,
                'another' => [
                    'some'  => 1,
                    'value' => 2,
                ],
            ]);

        $service = new LpaService($this->apiClientProphecy->reveal());

        $lpa = $service->search($passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(123456789012, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }
}
