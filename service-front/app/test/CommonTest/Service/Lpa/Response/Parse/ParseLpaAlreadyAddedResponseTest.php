<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Entity\CaseActor;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ParseLpaAlreadyAddedResponseTest extends TestCase
{
    use ProphecyTrait;

    private CaseActor $donor;

    private ObjectProphecy|LpaFactory $lpaFactory;
    private array $response;

    public function setUp(): void
    {
        $this->response = [
            'donor'         => [
                'uId'        => '12345',
                'firstnames' => 'Example Donor',
                'surname'    => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'abc-321',
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example Donor');
        $this->donor->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    #[Test]
    public function it_creates_an_already_added_dto_from_array_data(): void
    {
        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($this->donor);

        $sut    = new ParseLpaAlreadyAdded($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertEquals($this->donor, $result->getDonor());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertEquals('abc-321', $result->getLpaActorToken());
    }

    #[Test]
    public function it_creates_an_already_added_dto_from_array_data_with_null_name_fields(): void
    {
        $this->response['donor']['firstnames'] = null;
        $this->response['donor']['surname']    = null;

        $donor = new CaseActor();
        $donor->setUId('12345');

        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($donor);

        $sut    = new ParseLpaAlreadyAdded($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertNull($result->getDonor()->getFirstname());
        $this->assertNull($result->getDonor()->getMiddlenames());
        $this->assertNull($result->getDonor()->getSurname());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertEquals('abc-321', $result->getLpaActorToken());
    }

    #[Test]
    public function it_will_fail_if_donor_firstname_array_key_doesnt_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'     => '12345',
                'surname' => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'abc-321',
        ];

        $sut = new ParseLpaAlreadyAdded($this->lpaFactory->reveal());
        ($sut)($data);
    }

    #[Test]
    public function it_will_fail_if_donor_surname_array_key_doesnt_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'         => [
                'uId'        => '12345',
                'firstnames' => 'Donor Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'abc-321',
        ];

        $sut = new ParseLpaAlreadyAdded($this->lpaFactory->reveal());
        ($sut)($data);
    }

    #[DataProvider('alreadyAddedDataProvider')]
    #[Test]
    public function it_will_fail_if_donor_uId_or_lpa_type_or_token_is_not_set(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded::__invoke ' .
             'does not contain the required fields'
        );

        $sut = new ParseLpaAlreadyAdded($this->lpaFactory->reveal());
        ($sut)($data);
    }

    public static function alreadyAddedDataProvider(): array
    {
        return [
            [
                [
                    'donor'         => null,
                    'caseSubtype'   => 'hw',
                    'lpaActorToken' => 'abc-321',
                ],
            ],
            [
                [
                    'donor'         => [
                        'uId'        => null,
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype'   => 'hw',
                    'lpaActorToken' => 'abc-321',
                ],
            ],
            [
                [
                    'donor'         => [
                        'uId'        => '12345',
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype'   => null,
                    'lpaActorToken' => 'abc-321',
                ],
            ],
            [
                [
                    'donor'         => [
                        'uId'        => '12345',
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype'   => 'hw',
                    'lpaActorToken' => null,
                ],
            ],
        ];
    }
}
