<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\Factory\LpaDataFormatter;
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
    public function test_lpa_donor_signature_date_does_not_serialise()
    {

        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $serialisedLpa = $this->lpaDataFormatter->serializeObject($combinedLpa);

        $this->assertEquals(null, $combinedLpa->getLpaDonorSignatureDate());
    }

}