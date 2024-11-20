<?php

declare(strict_types=1);

namespace CommonTest\Enum;

use Common\Enum\LpaType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaTypeTest extends TestCase
{
    #[Test]
    public function test_lpa_type_enum_has_issers(): void
    {
        $propertyAndAffairs = LpaType::PROPERTY_AND_AFFAIRS;
        $personalWelfare    = LpaType::PERSONAL_WELFARE;

        $this->assertTrue($propertyAndAffairs->isPropertyAndAffairs());
        $this->assertFalse($propertyAndAffairs->isPersonalWelfare());

        $this->assertTrue($personalWelfare->isPersonalWelfare());
        $this->assertFalse($personalWelfare->isPropertyAndAffairs());
    }
}
