<?php

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Entity\CaseActor;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ParseActivationKeyExistsResponseTest extends TestCase
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
            'caseSubtype' => 'hw'
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example');
        $this->donor->setMiddlenames('Donor');
        $this->donor->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    public function it_creates_a_dto_from_array_data()
    {
        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $donor = new CaseActor();
        $donor->setUId($this->response['donor']['uId']);
        $donor->setFirstname($this->response['donor']['firstname']);
        $donor->setMiddlenames($this->response['donor']['middlenames']);
        $donor->setSurname($this->response['donor']['surname']);

        $this->assertInstanceOf(ActivationKeyExistsResponse::class, $result);
        $this->assertEquals($donor, $result->getDonor());
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

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    public function keyExistsDataProvider()
    {
        return [
            [
                [
                    'donor'         => [
                        'uId'           => '12345',
                        'firstname'     => null,
                        'middlenames'   => 'Donor',
                        'surname'       => 'Person',
                    ],
                    'caseSubtype' => 'hw'
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
                    'caseSubtype' => null
                ]
            ]
        ];
    }
}
