<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response;

use PHPUnit\Framework\Attributes\Test;
use Common\Entity\CaseActor;
use Common\Service\Lpa\Response\LpaMatch;
use PHPUnit\Framework\TestCase;

class LpaMatchResponseTest extends TestCase
{
    #[Test]
    public function it_can_create_a_response_dto(): void
    {
        $dto = new LpaMatch();

        $this->assertInstanceOf(LpaMatch::class, $dto);
    }

    #[Test]
    public function it_allows_donor_and_attorney_name_and_lpa_type_to_be_set_and_get(): void
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $attorney = new CaseActor();
        $attorney->setUId('12378');
        $attorney->setFirstname('Example');
        $attorney->setMiddlenames('Attorney');
        $attorney->setSurname('Person');

        $dto = new LpaMatch();
        $dto->setDonor($donor);
        $dto->setAttorney($attorney);
        $dto->setCaseSubtype('pfa');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEquals($attorney, $dto->getAttorney());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
    }

    #[Test]
    public function it_allows_donor_and_lpa_type_and_empty_attorney_to_be_set_and_get(): void
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $attorney = new CaseActor();
        $attorney->setUId('12378');
        $attorney->setFirstname('');
        $attorney->setMiddlenames('');
        $attorney->setSurname('');

        $dto = new LpaMatch();
        $dto->setDonor($donor);
        $dto->setAttorney($attorney);
        $dto->setCaseSubtype('pfa');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEmpty($dto->getAttorney()->getFirstname());
        $this->assertEmpty($dto->getAttorney()->getSurname());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
    }

    #[Test]
    public function it_allows_donor_name_and_lpa_type_to_be_set_and_get(): void
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new LpaMatch();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertNull($dto->getAttorney());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
    }

    #[Test]
    public function it_allows_lpa_activation_key_due_date_to_be_set(): void
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new LpaMatch();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');
        $dto->setDueDate('2021-12-06');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertNull($dto->getAttorney());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
        $this->assertEquals('2021-12-06', $dto->getDueDate());
    }
}
