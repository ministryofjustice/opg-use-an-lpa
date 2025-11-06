<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Service\Lpa\Factory\Sirius;
use DateTime;
use Laminas\Stdlib\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SiriusLpaFactoryTest extends TestCase
{
    use ProphecyTrait;

    protected array $fullExampleFixtureData;
    protected array $simpleExampleFixtureData;

    public function setUp(): void
    {
        $this->fullExampleFixtureData   = json_decode(
            file_get_contents(__DIR__ . '/../../../fixtures/full_example.json'),
            true
        );
        $this->simpleExampleFixtureData = json_decode(
            file_get_contents(__DIR__ . '/../../../fixtures/simple_example.json'),
            true
        );
    }

    #[Test]
    public function bad_data_throws_exception_in_create_lpa(): void
    {
        $factory = new Sirius();

        $this->expectException(InvalidArgumentException::class);
        $lpa = $factory->createLpaFromData([]);
    }

    #[Test]
    public function bad_data_throws_exception_in_create_case_actor(): void
    {
        $factory = new Sirius();

        $this->expectException(InvalidArgumentException::class);
        $caseActor = $factory->createCaseActorFromData([]);
    }

    #[Test]
    public function bad_data_throws_exception_in_create_address(): void
    {
        $factory = new Sirius();

        $this->expectException(InvalidArgumentException::class);
        $address = $factory->createAddressFromData([]);
    }

    #[Test]
    public function can_create_empty_lpa(): void
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData(['uId' => '1234']);

        $this->assertEquals('1234', $lpa->getUId());
    }

    #[Test]
    public function can_create_lpa_from_swagger_example(): void
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData($this->fullExampleFixtureData);

        $this->assertEquals('700000000054', $lpa->getUId()); // from full_example.json
        $this->assertNull($lpa->getCancellationDate());


        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1975-10-05'), $lpa->getAttorneys()[0]->getDob()); // from full_example.json
        $this->assertEquals(true, $lpa->getAttorneys()[0]->getSystemStatus());

        $this->assertInstanceOf(CaseActor::class, $lpa->getActiveAttorneys()[0]);
        $this->assertInstanceOf(CaseActor::class, $lpa->getInactiveAttorneys()[0]);
    }

    #[Test]
    public function can_create_lpa_from_simple_example(): void
    {
        $factory = new Sirius();

        $lpa = $factory->createLpaFromData($this->simpleExampleFixtureData);
        $this->assertEquals('7000-0000-0054', $lpa->getUId()); // from simple_example.json
        $this->assertEquals(new DateTime('2020-02-02'), $lpa->getCancellationDate());
        $this->assertEquals(null, $lpa->getRejectedDate());
        $this->assertEquals(null, $lpa->getLifeSustainingTreatment());
        $this->assertEquals(false, $lpa->getHasSeveranceWarning());

        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1980-10-10'), $lpa->getAttorneys()[0]->getDob()); // from simple_example.json
        $this->assertEquals(true, $lpa->getAttorneys()[0]->getSystemStatus());

        $this->assertCount(0, $lpa->getReplacementAttorneys());

        $this->assertEquals([0], $lpa->getDonor()->getIds());
    }

    #[Test]
    public function can_hydrate_donor_from_simple_example(): void
    {
        $factory = new Sirius();

        $lpa   = $factory->createLpaFromData($this->simpleExampleFixtureData);
        $donor = $lpa->getDonor();

        $this->assertEquals(0, $donor->getId());
        $this->assertEquals('7000-0000-0054', $donor->getUId());
        $this->assertEquals('string', $donor->getEmail());
        $this->assertEquals('Mrs', $donor->getSalutation());
        $this->assertEquals('Ian', $donor->getFirstname());
        $this->assertEquals(null, $donor->getOtherNames());
        $this->assertEquals(null, $donor->getMiddlenames());
        $this->assertEquals('Deputy', $donor->getSurname());
        $this->assertEquals('ABC Ltd', $donor->getCompanyName());
    }

    #[Test]
    public function can_hydrate_attorney_from_simple_example(): void
    {
        $factory = new Sirius();

        $lpa      = $factory->createLpaFromData($this->simpleExampleFixtureData);
        $attorney = $lpa->getAttorneys()[0];

        $this->assertEquals(0, $attorney->getId());
        $this->assertEquals('7000-0000-0054', $attorney->getUId());
        $this->assertEquals('string', $attorney->getEmail());
        $this->assertEquals('Mrs', $attorney->getSalutation());
        $this->assertEquals('Ian', $attorney->getFirstname());
        $this->assertEquals('George', $attorney->getOtherNames());
        $this->assertEquals('Deputy', $attorney->getMiddlenames());
        $this->assertEquals('Deputy', $attorney->getSurname());
        $this->assertEquals('ABC Ltd', $attorney->getCompanyName());
    }

    #[Test]
    public function can_create_lpa_from_example_with_linked_donors(): void
    {
        $factory = new Sirius();

        $data                    = $this->simpleExampleFixtureData;
        $data['donor']['linked'] = [
            ['id' => 5, 'uId' => '7000-0000-0033'],
            ['id' => 6, 'uId' => '7000-0000-0133'],
        ];

        $lpa = $factory->createLpaFromData($data);
        $this->assertEquals('7000-0000-0054', $lpa->getUId()); // from simple_example.json

        $this->assertEquals(null, $lpa->getRejectedDate());
        $this->assertEquals(null, $lpa->getLifeSustainingTreatment());

        $this->assertInstanceOf(CaseActor::class, $lpa->getDonor());
        $this->assertInstanceOf(Address::class, $lpa->getDonor()->getAddresses()[0]);

        $this->assertInstanceOf(CaseActor::class, $lpa->getAttorneys()[0]);
        $this->assertEquals(new DateTime('1980-10-10'), $lpa->getAttorneys()[0]->getDob()); // from simple_example.json
        $this->assertEquals(true, $lpa->getAttorneys()[0]->getSystemStatus());

        $this->assertCount(0, $lpa->getReplacementAttorneys());

        $this->assertEquals([5, 6], $lpa->getDonor()->getIds());
    }

    #[Test]
    public function firstnames_is_an_alias_of_firstname(): void
    {
        $factory = new Sirius();

        $data = [
            'uId'        => '7000-0000-0033',
            'firstnames' => 'John Stewart',
            'surname'    => 'Doe',
        ];

        $donor = $factory->createCaseActorFromData($data);

        $this->assertEquals('John Stewart', $donor->getFirstname());
    }
}
