<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Service\Lpa\AddLpaApiResult;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass \Common\Service\Lpa\AddLpaApiResult
 */
class AddLpaApiResponseTest extends TestCase
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
        $response = new AddLpaApiResult($responseType, $additionalData);
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
     * @return array
     */
    public function validDataTypeProvider()
    {
        return [
            [AddLpaApiResult::ADD_LPA_ALREADY_ADDED, $this->createAlreadyAddedDTO()],
            [AddLpaApiResult::ADD_LPA_FOUND, new ArrayObject(['lpa' => 'data'])],
            [AddLpaApiResult::ADD_LPA_NOT_FOUND, []],
            [AddLpaApiResult::ADD_LPA_NOT_ELIGIBLE, []],
            [AddLpaApiResult::ADD_LPA_SUCCESS,[]],
            [AddLpaApiResult::ADD_LPA_FAILURE,[]],
        ];
    }

    /**
     * @test
     * @covers       ::__construct
     * @covers       ::validateDataType
     * @dataProvider invalidDataTypeProvider
     */
    public function it_throws_an_exception_with_an_unrecognised_response_data_type($data): void
    {
        $this->expectException(RuntimeException::class);
        new AddLpaApiResult(AddLpaApiResult::ADD_LPA_ALREADY_ADDED, $data);
    }

    /**
     * @return array
     */
    public function invalidDataTypeProvider()
    {
        return [
            [3],
            [3.1],
            ['i am a string'],
            [false],
            [null],
        ];
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::validateResponseType
     */
    public function it_throws_an_exception_with_an_unrecognised_response_type(): void
    {
        $this->expectException(RuntimeException::class);
        new AddLpaApiResult('BAD TYPE', new ArrayObject());
    }
}
