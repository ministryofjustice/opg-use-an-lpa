<?php

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Entity\CaseActor;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\OlderLpaMatchResponse;
use Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ParseOlderLpaMatchResponseTest extends TestCase
{
    private CaseActor $donor;
    private CaseActor $attorney;
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
            'attorney'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Attorney',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example');
        $this->donor->setMiddlenames('Donor');
        $this->donor->setSurname('Person');

        $this->attorney = new CaseActor();
        $this->attorney->setUId('12378');
        $this->attorney->setFirstname('Example');
        $this->attorney->setMiddlenames('Attorney');
        $this->attorney->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    /** @test */
    public function it_creates_an_already_added_dto_from_array_data()
    {
        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($this->donor);

        $this->lpaFactory
            ->createCaseActorFromData($this->response['attorney'])
            ->willReturn($this->attorney);

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertInstanceOf(OlderLpaMatchResponse::class, $result);
        $this->assertEquals($this->donor, $result->getDonor());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertNotNull($result->getAttorney());
    }

    /** @test */
    public function it_creates_an_already_added_dto_from_array_data_with_null_name_fields()
    {
        $this->response['donor']['firstname'] = null;
        $this->response['donor']['middlenames'] = null;
        $this->response['donor']['surname'] = null;

        $donor = new CaseActor();
        $donor->setUId('12345');

        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($donor);

        $this->lpaFactory
            ->createCaseActorFromData($this->response['attorney'])
            ->willReturn($this->attorney);

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertInstanceOf(OlderLpaMatchResponse::class, $result);
        $this->assertNull($result->getDonor()->getFirstname());
        $this->assertNull($result->getDonor()->getMiddlenames());
        $this->assertNull($result->getDonor()->getSurname());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertNotNull($result->getAttorney());
    }

    /** @test */
    public function it_will_fail_if_donor_firstname_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /** @test */
    public function it_will_fail_if_donor_middlenames_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw'
        ];

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /** @test */
    public function it_will_fail_if_donor_surname_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Donor',
                'middlenames'   => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /**
     * @dataProvider alreadyAddedDataProvider
     * @test
     */
    public function it_will_fail_if_donor_uId_or_lpa_type_is_not_set(array $data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse::__invoke ' .
             'does not contain the required fields'
        );

        $sut = new ParseOlderLpaMatchResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    public function alreadyAddedDataProvider()
    {
        return [
            [
                [
                    'donor'         => null,
                    'caseSubtype' => 'hw'
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
