<?php

declare(strict_types=1);

namespace ViewerTest\Service\Lpa;

use PHPUnit\Framework\TestCase;
use Viewer\Service\Lpa\LpaFactory;
use Viewer\Entity\Lpa;
use Viewer\Entity\Address;
use Viewer\Entity\CaseActor;
use \DateTime;

class LpaFactoryTest extends TestCase
{
    /** @var array */
    protected $fullExampleFixtureData;

    public function setUp() 
    {
        $this->fullExampleFixtureData = json_decode(file_get_contents(__DIR__ . '/fixtures/full_example.json'), true);
    }

    public function testBadDataThrowsException()
    {
        $factory = new LpaFactory();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $lpa = $factory->createLpaFromData([]);
    }

    public function testCanCreateEmptyLpa()
    {
        $factory = new LpaFactory();

        $lpa = $factory->createLpaFromData(['uId' => '1234']);

        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals('1234', $lpa->getUId());
    }

    public function testCanCreateLpaFromSwaggerExample()
    {
        $factory = new LpaFactory();

        $lpa = $factory->createLpaFromData($this->fullExampleFixtureData);
        $this->assertInstanceOf(Lpa::class, $lpa);
        $this->assertEquals('7000-0000-0054', $lpa->getUId()); // from full_example.json

        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1980-10-10'), $lpa->getAttorneys()[0]->getDob());
    }
}