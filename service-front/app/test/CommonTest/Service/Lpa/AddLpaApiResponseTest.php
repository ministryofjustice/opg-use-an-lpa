<?php

namespace CommonTest\Service\Lpa;

use Common\Service\Lpa\AddLpaApiResponse;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ArrayObject;

/**
 * Class AddLpaApiResponseTest
 *
 * @package CommonTest\Service\Lpa
 * @coversDefaultClass \Common\Service\Lpa\AddLpaApiResponse
 */
class AddLpaApiResponseTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::validateDataType
     * @covers ::validateResponseType
     * @dataProvider validDataTypeProvider
     */
    public function it_can_be_created_with_a_recognised_response_and_data_type($responseType, $additionalData): void
    {
        $response = new AddLpaApiResponse($responseType, $additionalData);
        $this->assertEquals($responseType, $response->getResponse());
        $this->assertEquals($additionalData, $response->getData());
    }

    /**
     * Creates the already added DTO for the data provider
     *
     * @return LpaAlreadyAddedResponse
     */
    private function createAlreadyAddedDTO()
    {
        $dto = new LpaAlreadyAddedResponse();
        $dto->setDonorName('Donor Person');
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
            [AddLpaApiResponse::ADD_LPA_ALREADY_ADDED, $this->createAlreadyAddedDTO()],
            [AddLpaApiResponse::ADD_LPA_FOUND, new ArrayObject(['lpa' => 'data'])],
            [AddLpaApiResponse::ADD_LPA_NOT_FOUND, []],
            [AddLpaApiResponse::ADD_LPA_NOT_ELIGIBLE, []],
            [AddLpaApiResponse::ADD_LPA_SUCCESS,[]],
            [AddLpaApiResponse::ADD_LPA_FAILURE,[]],
        ];
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::validateDataType
     * @dataProvider invalidDataTypeProvider
     */
    public function it_throws_an_exception_with_an_unrecognised_response_data_type($data): void
    {
        $this->expectException(RuntimeException::class);
        new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_ALREADY_ADDED, $data);
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
            [null]
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
        new AddLpaApiResponse('BAD TYPE', new ArrayObject());
    }
}
