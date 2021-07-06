<?php

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ParseActivationKeyExistsResponseTest extends TestCase
{
    public function it_creates_a_dto_from_array_data()
    {
        $sut = new ParseActivationKeyExistsResponse();
        $result = ($sut)(
            [
                'donorName'     => 'Donor Person',
                'caseSubtype'   => 'pfa'
            ]
        );

        $this->assertInstanceOf(ActivationKeyExistsResponse::class, $result);
        $this->assertEquals('Donor Person', $result->getDonorName());
        $this->assertEquals('pfa', $result->getCaseSubtype());
    }

    /**
     * @dataProvider keyExistsDataProvider
     * @test
     */
    public function it_will_fail_if_data_attributes_are_not_set(array $data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse::__invoke ' .
            'does not contain the required fields'
        );

        $sut = new ParseActivationKeyExistsResponse();
        ($sut)($data);
    }

    public function keyExistsDataProvider()
    {
        return [
            [
                [
                    'donorName'     => null,
                    'caseSubtype'   => 'pfa'
                ]
            ],
            [
                [
                    'donorName'     => 'Donor Person',
                    'caseSubtype'   => null
                ]
            ]
        ];
    }
}
