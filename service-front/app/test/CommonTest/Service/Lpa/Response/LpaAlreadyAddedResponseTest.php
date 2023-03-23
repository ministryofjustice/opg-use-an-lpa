<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response;

use Common\Entity\CaseActor;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use PHPUnit\Framework\TestCase;

class LpaAlreadyAddedResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_a_response_dto()
    {
        $dto = new LpaAlreadyAdded();

        $this->assertInstanceOf(LpaAlreadyAdded::class, $dto);
    }

    /** @test */
    public function it_allows_donor_name_and_lpa_type_and_token_to_be_set_and_get()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new LpaAlreadyAdded();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');
        $dto->setLpaActorToken('abc-321');

        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
        $this->assertEquals('abc-321', $dto->getLpaActorToken());
    }
}
