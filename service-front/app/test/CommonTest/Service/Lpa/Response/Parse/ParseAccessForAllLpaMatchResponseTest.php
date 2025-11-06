<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response\Parse;

use Common\Entity\CaseActor;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\Parse\ParseLpaMatch;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ParseAccessForAllLpaMatchResponseTest extends TestCase
{
    use ProphecyTrait;

    private CaseActor $donor;
    private CaseActor $attorney;

    private ObjectProphecy|LpaFactory $lpaFactory;

    /** @var array<string, string|array<string, ?string>> $response */
    private array $response;

    public function setUp(): void
    {
        $this->response = [
            'donor'       => [
                'uId'        => '12345',
                'firstnames' => 'Example Donor',
                'surname'    => 'Person',
            ],
            'attorney'    => [
                'uId'        => '12345',
                'firstnames' => 'Example Attorney',
                'surname'    => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $this->donor = new CaseActor();
        $this->donor->setUId('12345');
        $this->donor->setFirstname('Example Donor');
        $this->donor->setSurname('Person');

        $this->attorney = new CaseActor();
        $this->attorney->setUId('12378');
        $this->attorney->setFirstname('Example Attorney');
        $this->attorney->setSurname('Person');

        $this->lpaFactory = $this->prophesize(LpaFactory::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_creates_a_lpa_match_actor_details_dto_from_array_data(): void
    {
        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($this->donor);

        $this->lpaFactory
            ->createCaseActorFromData($this->response['attorney'])
            ->willReturn($this->attorney);

        $sut    = new ParseLpaMatch($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertEquals($this->donor, $result->getDonor());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertNotNull($result->getAttorney());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_creates_a_lpa_match_actor_details_dto_from_array_data_with_null_name_fields(): void
    {
        $this->response['donor']['firstnames'] = null;
        $this->response['donor']['surname']    = null;

        $donor = new CaseActor();
        $donor->setUId('12345');

        $this->lpaFactory
            ->createCaseActorFromData($this->response['donor'])
            ->willReturn($donor);

        $this->lpaFactory
            ->createCaseActorFromData($this->response['attorney'])
            ->willReturn($this->attorney);

        $sut    = new ParseLpaMatch($this->lpaFactory->reveal());
        $result = ($sut)($this->response);

        $this->assertNull($result->getDonor()->getFirstname());
        $this->assertNull($result->getDonor()->getSurname());
        $this->assertEquals('hw', $result->getCaseSubtype());
        $this->assertNotNull($result->getAttorney());
    }

    #[Test]
    public function it_will_fail_if_lpa_match_actor_donor_firstname_array_key_doesnt_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaMatch::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'       => [
                'uId'     => '12345',
                'surname' => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $sut = new ParseLpaMatch($this->lpaFactory->reveal());
        ($sut)($data);
    }

    #[Test]
    public function it_will_fail_if_lpa_match_actor_donor_surname_array_key_doesnt_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaMatch::__invoke ' .
            'does not contain the required fields'
        );

        $data = [
            'donor'       => [
                'uId'        => '12345',
                'firstnames' => 'Donor Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $sut = new ParseLpaMatch($this->lpaFactory->reveal());
        ($sut)($data);
    }

    #[DataProvider('lpaMatchActorDetailsDataProvider')]
    #[Test]
    public function it_will_fail_if_lpa_match_actor_uId_or_lpa_type_is_not_set(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The data array passed to Common\Service\Lpa\Response\Parse\ParseLpaMatch::__invoke ' .
             'does not contain the required fields'
        );

        $sut = new ParseLpaMatch($this->lpaFactory->reveal());
        ($sut)($data);
    }

    public static function lpaMatchActorDetailsDataProvider(): array
    {
        return [
            [
                [
                    'donor'       => null,
                    'caseSubtype' => 'hw',
                ],
            ],
            [
                [
                    'attorney'    => [
                        'uId'        => null,
                        'firstnames' => 'Example Attorney',
                        'surname'   => 'Person',
                    ],
                    'donor'       => [
                        'uId'        => null,
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype' => 'hw',
                ],
            ],
            [
                [
                    'attorney'    => [
                        'uId'        => '12378',
                        'firstnames' => 'Example Attorney',
                        'surname'    => 'Person',
                    ],
                    'donor'       => [
                        'uId'        => '12345',
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype' => null,
                ],
            ],
            [
                [
                    'attorney'    => [
                        'uId'        => '12378',
                        'firstnames' => 'Example Attorney',
                        'surname'    => 'Person',
                    ],
                    'donor'       => [
                        'uId'        => '12345',
                        'firstnames' => 'Example Donor',
                        'surname'    => 'Person',
                    ],
                    'caseSubtype' => null,
                ],
            ],
        ];
    }
}
