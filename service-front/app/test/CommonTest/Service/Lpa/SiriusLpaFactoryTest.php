<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Service\Lpa\Factory\Sirius;
use PHPUnit\Framework\TestCase;
use DateTime;

class LpaFactoryTest extends TestCase
{
    /** @var array */
    protected $fullExampleFixtureData;

    /** @var array */
    protected $simpleExampleFixtureData;

    public function setUp()
    {
        $this->fullExampleFixtureData = json_decode(file_get_contents(__DIR__ . '/fixtures/full_example.json'), true);
        $this->simpleExampleFixtureData = json_decode(file_get_contents(__DIR__ . '/fixtures/simple_example.json'), true);
    }

    public function testBadDataThrowsExceptionInCreateLpa()
    {
        $factory = new Sirius();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $lpa = $factory->createLpaFromData([]);
    }

    public function testBadDataThrowsExceptionInCreateCaseActor()
    {
        $factory = new Sirius();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $caseActor = $factory->createCaseActorFromData([]);
    }

    public function testBadDataThrowsExceptionInCreateAddress()
    {
        $factory = new Sirius();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $address = $factory->createAddressFromData([]);
    }

    public function testCanCreateEmptyLpa()
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData(['uId' => '1234']);

        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals('1234', $lpa->getUId());
    }

    public function testCanCreateLpaFromSwaggerExample()
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData($this->fullExampleFixtureData);
        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals('7000-0000-0054', $lpa->getUId()); // from full_example.json

        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1980-10-10'), $lpa->getAttorneys()[0]->getDob()); // from full_example.json
        $this->assertEquals(true, $lpa->getAttorneys()[0]->getSystemStatus());
    }

    public function testCanCreateLpaFromSimpleExample()
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData($this->simpleExampleFixtureData);
        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals('7000-0000-0054', $lpa->getUId()); // from simple_example.json

        $this->assertEquals(null, $lpa->getRejectedDate());
        $this->assertEquals(null, $lpa->getLifeSustainingTreatment());

        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1980-10-10'), $lpa->getAttorneys()[0]->getDob()); // from simple_example.json
        $this->assertEquals(true, $lpa->getAttorneys()[0]->getSystemStatus());

        $this->assertCount(0, $lpa->getReplacementAttorneys());
    }
}