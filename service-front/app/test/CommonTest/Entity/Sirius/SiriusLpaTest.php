<?php

declare(strict_types=1);

namespace CommonTest\Entity\Sirius;

use Common\Entity\Sirius\SiriusLpaDonor;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SiriusLpaTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_get_donor_test()
    {
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $expectedDonor = new SiriusLpaDonor(
            addressLine1:  '81 Front Street',
            addressLine2:  'LACEBY',
            addressLine3: '',
            country: '',
            county: '',
            dob: new DateTimeImmutable('1948-11-01'),
            email: 'RachelSanderson@opgtest.com',
            firstname: 'Rachel',
            firstnames: null,
            linked: [
                [
                    'id'  => 7,
                    'uId' => '700000000799',
                ],
            ],
            name: null,
            otherNames: null,
            postcode: 'DN37 5SH',
            surname: 'Sanderson',
            systemStatus: null,
            town: '',
            type: 'Primary',
            uId: '700000000799'
        );

        $this->assertEquals($expectedDonor, $combinedLpa->getDonor());
    }

    #[Test]
    public function can_get_case_sub_type()
    {
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $expectedCaseSubType = 'hw';

        $this->assertEquals($expectedCaseSubType, $combinedLpa->getCaseSubType());
    }
}
