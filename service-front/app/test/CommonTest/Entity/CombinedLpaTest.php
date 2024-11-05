<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Service\Lpa\Factory\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CombinedLpaTest extends TestCase
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

        $expectedUid                    = '700000000047';
        $expectedApplicationHasGuidance = false;
        $expectedHasRestrictions        = false;

        $this->assertEquals($expectedUid, $combinedLpa->getUId());
        $this->assertEquals($expectedApplicationHasGuidance, $combinedLpa->getApplicationHasGuidance());
        $this->assertEquals($expectedHasRestrictions, $combinedLpa->getApplicationHasRestrictions());
    }
}
