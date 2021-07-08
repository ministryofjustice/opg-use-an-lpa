<?php

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Entity\CaseActor;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ParseLpaAlreadyAddedResponseTest extends TestCase
{
    private CaseActor $donor;
    /** @var ObjectProphecy|LpaFactory */
    private $lpaFactory;
    private array $response;

    public function setUp(): void
    {
        $this->response = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'abc-321'
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example');
        $this->donor->setMiddlenames('Donor');
        $this->donor->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    public function it_creates_a_already_added_dto_from_array_data()
    {
        $sut = new ParseLpaAlreadyAddedResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertInstanceOf(LpaAlreadyAddedResponse::class, $result);
        $this->assertEquals($this->donor, $result->getDonor());
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

        $sut = new ParseLpaAlreadyAddedResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    public function alreadyAddedDataProvider()
    {
        return [
            [
                [
                    'donor'         => null,
                    'caseSubtype' => 'hw',
                    'lpaActorToken' => 'abc-321'
                ]
            ],
            [
                [
                    'donor'         => [
                        'uId'           => null,
                        'firstname'     => 'Example',
                        'middlenames'   => 'Donor',
                        'surname'       => 'Person',
                    ],
                    'caseSubtype' => 'hw',
                    'lpaActorToken' => 'abc-321'
                ]
            ],
            [
                [
                    'donor'         => [
                        'uId'           => '12345',
                        'firstname'     => null,
                        'middlenames'   => 'Donor',
                        'surname'       => 'Person',
                    ],
                    'caseSubtype' => 'hw',
                    'lpaActorToken' => 'abc-321'
                ]
            ],
            [
                [
                    'donor'         => [
                        'uId'           => '12345',
                        'firstname'     => 'Example',
                        'middlenames'   => 'Donor',
                        'surname'       => 'Person',
                    ],
                    'caseSubtype' => null,
                    'lpaActorToken' => 'abc-321'
                ]
            ],
            [
                [
                    'donor'         => [
                        'uId'           => '12345',
                        'firstname'     => 'Example',
                        'middlenames'   => 'Donor',
                        'surname'       => 'Person',
                    ],
                    'caseSubtype' => 'hw',
                    'lpaActorToken' => null
                ]
            ],
        ];
    }
}
