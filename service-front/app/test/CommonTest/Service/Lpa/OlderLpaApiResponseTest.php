<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class OlderLpaApiResponseTest
 *
 * @package CommonTest\Service\Lpa
 * @coversDefaultClass \Common\Service\Lpa\OlderLpaApiResponse
 */
class OlderLpaApiResponseTest extends TestCase
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
        $response = new OlderLpaApiResponse($responseType, $additionalData);
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
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new LpaAlreadyAddedResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype('hw');
        $dto->setLpaActorToken('abc');
        return $dto;
    }

    /**
     * Creates the key exists DTO for the data provider
     *
     * @return ActivationKeyExistsResponse
     */
    private function createActivationKeyExistsDTO()
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new ActivationKeyExistsResponse();
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
            [OlderLpaApiResponse::LPA_ALREADY_ADDED, $this->createAlreadyAddedDTO()],
            [OlderLpaApiResponse::HAS_ACTIVATION_KEY, $this->createActivationKeyExistsDTO()],
            [OlderLpaApiResponse::DOES_NOT_MATCH, []],
            [OlderLpaApiResponse::NOT_ELIGIBLE, []],
            [OlderLpaApiResponse::NOT_FOUND, []],
            [OlderLpaApiResponse::SUCCESS,[]],
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
        new OlderLpaApiResponse(OlderLpaApiResponse::LPA_ALREADY_ADDED, $data);
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
            [new ArrayObject()]
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
        new OlderLpaApiResponse('BAD TYPE', []);
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
            'test' => 'data'
        ];

        $sut = new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, $data);

        $this->assertEquals(OlderLpaApiResponse::SUCCESS, $sut->getResponse());
        $this->assertEquals($data, $sut->getData());
    }
}
