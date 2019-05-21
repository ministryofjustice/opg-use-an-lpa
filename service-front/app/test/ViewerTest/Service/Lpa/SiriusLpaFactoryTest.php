<?php

declare(strict_types=1);

namespace ViewerTest\Service\Lpa;

use PHPUnit\Framework\TestCase;
use Viewer\Entity\Lpa;
use Viewer\Entity\Address;
use Viewer\Entity\CaseActor;
use \DateTime;
use Viewer\Service\Lpa\Factory\Sirius;

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

    public function testBadDataThrowsException()
    {
        $factory = new Sirius();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $lpa = $factory->createLpaFromData([]);
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

        $this->assertCount(0, $lpa->getReplacementAttorneys());
    }
}