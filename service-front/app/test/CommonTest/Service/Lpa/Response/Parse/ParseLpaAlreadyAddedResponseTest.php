<?php

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ParseLpaAlreadyAddedResponseTest extends TestCase
{
    public function it_creates_a_already_added_dto_from_array_data()
    {
        $sut = new ParseLpaAlreadyAddedResponse();
        $result = ($sut)(
            [
                'donorName'     => 'Donor Person',
                'caseSubtype'   => 'pfa',
                'lpaActorToken' => 'abc-321'
            ]
        );

        $this->assertInstanceOf(LpaAlreadyAddedResponse::class, $result);
        $this->assertEquals('Donor Person', $result->getDonorName());
        $this->assertEquals('pfa', $result->getCaseSubtype());
        $this->assertEquals('abc-321', $result->getLpaActorToken());
    }

    /**
     * @dataProvider alreadyAddedDataProvider
     * @test
     */
    public function it_will_fail_if_data_attributes_are_not_set(array $data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse::__invoke ' .
             'does not contain the required fields'
        );

        $sut = new ParseLpaAlreadyAddedResponse();
        ($sut)($data);
    }

    public function alreadyAddedDataProvider()
    {
        return [
            [
                [
                    'donorName'     => null,
                    'caseSubtype'   => 'pfa',
                    'lpaActorToken' => 'abc'
                ]
            ],
            [
                [
                    'donorName'     => 'Donor Person',
                    'caseSubtype'   => null,
                    'lpaActorToken' => 'abc'
                ]
            ],
            [
                [
                    'donorName'     => 'Donor Person',
                    'caseSubtype'   => 'pfa',
                    'lpaActorToken' => null
                ]
            ],
        ];
    }
}
