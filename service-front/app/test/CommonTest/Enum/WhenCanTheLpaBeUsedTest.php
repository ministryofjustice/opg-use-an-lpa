<?php

declare(strict_types=1);

namespace CommonTest\Enum;

use Common\Enum\WhenTheLpaCanBeUsed;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WhenCanTheLpaBeUsedTest extends TestCase
{
    #[Test]
    public function test_when_can_the_lpa_be_used_enum_has_issers(): void
    {
        $whenHasCapacity  = WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY;
        $whenCapacityLost = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST;
        $unknown          = WhenTheLpaCanBeUsed::UNKNOWN;

        $this->assertTrue($whenHasCapacity->isWhenHasCapacity());
        $this->assertFalse($whenHasCapacity->isUnknown());

        $this->assertTrue($whenCapacityLost->isWhenHasLostCapacity());
        $this->assertFalse($whenCapacityLost->isUnknown());

        $this->assertTrue($unknown->isUnknown());
        $this->assertFalse($unknown->isWhenHasLostCapacity());
    }
}
