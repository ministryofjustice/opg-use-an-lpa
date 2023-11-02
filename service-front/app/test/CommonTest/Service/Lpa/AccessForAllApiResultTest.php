<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Service\Lpa\AccessForAllApiResult;
use Common\Service\Lpa\Response\AccessForAllResult;
use Common\Service\Lpa\Response\ActivationKeyExists;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Common\Service\Lpa\Response\AccessForAllResult
 */
class AccessForAllApiResultTest extends TestCase
{
    /**
     * @test
     * @covers       ::__construct
     * @covers       ::validateDataType
     * @covers       ::validateResponseType
     * @dataProvider validDataTypeProvider
     */
    public function it_can_be_created_with_a_recognised_response_and_data_type($responseType, $additionalData): void
    {
        $response = new AccessForAllApiResult($responseType, $additionalData);
        $this->assertEquals($responseType, $response->getResponse());
        $this->assertEquals($additionalData, $response->getData());
    }

    /**
     * Creates the already added DTO for the data provider
     *
     * @return LpaAlreadyAdded
     */
    private function createAlreadyAddedDTO()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new LpaAlreadyAdded();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('hw');
        $dto->setLpaActorToken('abc');
        return $dto;
    }

    /**
     * Creates the key exists DTO for the data provider
     *
     * @return ActivationKeyExists
     */
    private function createActivationKeyExistsDTO()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new ActivationKeyExists();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('pfa');
        return $dto;
    }

    /**
     * @return array
     */
    public function validDataTypeProvider()
    {
        return [
            [AccessForAllResult::LPA_ALREADY_ADDED, $this->createAlreadyAddedDTO()],
            [AccessForAllResult::HAS_ACTIVATION_KEY, $this->createActivationKeyExistsDTO()],
            [AccessForAllResult::KEY_ALREADY_REQUESTED, $this->createActivationKeyExistsDTO()],
            [AccessForAllResult::DOES_NOT_MATCH, []],
            [AccessForAllResult::NOT_ELIGIBLE, []],
            [AccessForAllResult::NOT_FOUND, []],
            [AccessForAllResult::SUCCESS,[]],
            [AccessForAllResult::OLDER_LPA_NEEDS_CLEANSING,[]],
            [AccessForAllResult::POSTCODE_NOT_SUPPLIED,[]],
            [AccessForAllResult::STATUS_NOT_VALID,[]],
        ];
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::validateResponseType
     * @covers ::getResponse
     * @covers ::getData
     */
    public function it_makes_available_the_type_and_passed_in_additional_data(): void
    {
        $data = [
            'test' => 'data',
        ];

        $sut = new AccessForAllApiResult(AccessForAllResult::SUCCESS, $data);

        $this->assertEquals(AccessForAllResult::SUCCESS, $sut->getResponse());
        $this->assertEquals($data, $sut->getData());
    }
}
