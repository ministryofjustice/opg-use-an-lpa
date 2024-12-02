<?php

declare(strict_types=1);

namespace CommonTest\Enum;

use Common\Enum\LifeSustainingTreatment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LifeSustainingTreatmentTest extends TestCase
{
    #[Test]
    public function test_life_sustaining_treatment_enum_has_issers(): void
    {
        $optionA = LifeSustainingTreatment::OPTION_A;
        $optionB = LifeSustainingTreatment::OPTION_B;

        $this->assertTrue($optionA->isOptionA());
        $this->assertFalse($optionA->isOptionB());

        $this->assertTrue($optionB->isOptionB());
        $this->assertFalse($optionB->isOptionA());
    }
}
