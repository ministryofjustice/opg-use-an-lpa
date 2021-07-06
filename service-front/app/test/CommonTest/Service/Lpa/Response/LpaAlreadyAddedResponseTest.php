<?php

namespace CommonTest\Service\Lpa\Response;

use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use PHPUnit\Framework\TestCase;

class LpaAlreadyAddedResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_a_response_dto()
    {
        $dto = new LpaAlreadyAddedResponse();

        $this->assertInstanceOf(LpaAlreadyAddedResponse::class, $dto);
    }

    /** @test */
    public function it_allows_donor_name_and_lpa_type_and_token_to_be_set_and_get()
    {
        $dto = new LpaAlreadyAddedResponse();
        $dto->setDonorName('Donor Person');
        $dto->setCaseSubtype('pfa');
        $dto->setLpaActorToken('abc-321');

        $this->assertEquals('Donor Person', $dto->getDonorName());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
        $this->assertEquals('abc-321', $dto->getLpaActorToken());
    }

    /** @test */
    public function it_returns_a_null_attribute_when_not_set()
    {
        $dto = new LpaAlreadyAddedResponse();

        $this->assertNull($dto->getDonorName());
        $this->assertNull($dto->getCaseSubtype());
        $this->assertNull($dto->getLpaActorToken());
    }
}
