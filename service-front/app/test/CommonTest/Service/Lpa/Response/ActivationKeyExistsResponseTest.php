<?php

namespace CommonTest\Service\Lpa\Response;

use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use PHPUnit\Framework\TestCase;

class ActivationKeyExistsResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_a_response_dto()
    {
        $dto = new ActivationKeyExistsResponse();

        $this->assertInstanceOf(ActivationKeyExistsResponse::class, $dto);
    }

    /** @test */
    public function it_allows_donor_name_and_lpa_type_to_be_set_and_get()
    {
        $dto = new ActivationKeyExistsResponse();
        $dto->setDonorName('Donor Person');
        $dto->setCaseSubtype('pfa');

        $this->assertEquals('Donor Person', $dto->getDonorName());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
    }

    /** @test */
    public function it_returns_a_null_attribute_when_not_set()
    {
        $dto = new ActivationKeyExistsResponse();

        $this->assertNull($dto->getDonorName());
        $this->assertNull($dto->getCaseSubtype());
    }
}
