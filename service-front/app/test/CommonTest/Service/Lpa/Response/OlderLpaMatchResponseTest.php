<?php

namespace CommonTest\Service\Lpa\Response;

use Common\Entity\CaseActor;
use Common\Service\Lpa\Response\OlderLpaMatchResponse;
use PHPUnit\Framework\TestCase;

class OlderLpaMatchResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_a_response_dto()
    {
        $dto = new OlderLpaMatchResponse();

        $this->assertInstanceOf(OlderLpaMatchResponse::class, $dto);
    }

    /** @test */
    public function it_allows_donor_name_and_lpa_type_to_be_set_and_get()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $attorney = new CaseActor();

        $dto = new OlderLpaMatchResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');
        $dto->setAttorney($attorney);

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
    }
}
