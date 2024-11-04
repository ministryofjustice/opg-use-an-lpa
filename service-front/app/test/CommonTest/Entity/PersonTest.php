<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Service\Lpa\Factory\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PersonTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_test_getters()
    {
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $expectedSalutation = '';
        $expectedFirstname  = 'Rachel';
        $expectedMiddlename = '';
        $expectedSurname    = 'Sanderson';
        $expectedDob        = new DateTimeImmutable('1948-11-01');

        $this->assertEquals($expectedSalutation, $combinedLpa->getDonor()->getSalutation());
        $this->assertEquals($expectedFirstname, $combinedLpa->getDonor()->getFirstname());
        $this->assertEquals($expectedMiddlename, $combinedLpa->getDonor()->getMiddlenames());
        $this->assertEquals($expectedSurname, $combinedLpa->getDonor()->getSurname());
        $this->assertEquals($expectedDob, $combinedLpa->getDonor()->getDob());
    }
}
