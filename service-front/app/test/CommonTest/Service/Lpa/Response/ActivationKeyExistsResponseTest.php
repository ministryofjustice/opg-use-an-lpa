<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response;

use Common\Entity\CaseActor;
use Common\Service\Lpa\Response\ActivationKeyExists;
use PHPUnit\Framework\TestCase;

class ActivationKeyExistsResponseTest extends TestCase
{
    /**
     * @test 
     */
    public function it_can_create_a_response_dto()
    {
        $dto = new ActivationKeyExists();

        $this->assertInstanceOf(ActivationKeyExists::class, $dto);
    }

    /**
     * @test 
     */
    public function it_allows_donor_name_and_lpa_type_to_be_set_and_get()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new ActivationKeyExists();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');
        $dto->setDueDate('2021-12-06');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
        $this->assertEquals('2021-12-06', $dto->getDueDate());
    }
}
