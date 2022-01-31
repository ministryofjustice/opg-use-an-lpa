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
            'caseSubtype' => 'hw',
            'activationKeyDueDate' => '2021-12-06'
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example');
        $this->donor->setMiddlenames('Donor');
        $this->donor->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    /** @test */
    public function it_creates_a_dto_from_array_data()
    {
        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($this->donor);

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertInstanceOf(ActivationKeyExistsResponse::class, $result);
        $this->assertEquals($this->donor, $result->getDonor());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertEquals('2021-12-06', $result->getDueDate());
    }

    /** @test */
    public function it_creates_a_dto_from_array_data_if_name_fields_are_null()
    {
        $this->response['donor']['firstname'] = null;
        $this->response['donor']['middlenames'] = null;
        $this->response['donor']['surname'] = null;

        $donor = new CaseActor();
        $donor->setUId('12345');

        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($donor);

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertInstanceOf(ActivationKeyExistsResponse::class, $result);
        $this->assertNull($result->getDonor()->getFirstname());
        $this->assertNull($result->getDonor()->getMiddlenames());
        $this->assertNull($result->getDonor()->getSurname());
        $this->assertEquals('hw', $result->getCaseSubtype());
    }

    /** @test */
    public function it_will_fail_if_donor_firstname_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => null
        ];

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /** @test */
    public function it_will_fail_if_donor_middlenames_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => null
        ];

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /** @test */
    public function it_will_fail_if_donor_surname_array_key_doesnt_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Donor',
                'middlenames'   => 'Person',
            ],
            'caseSubtype' => null
        ];

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }

    /** @test */
    public function it_will_fail_if_lpa_type_is_not_set()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => null
        ];

        $sut = new ParseActivationKeyExistsResponse($this->lpaFactory->reveal());
        ($sut)($data);
    }
}
