<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use Common\Enum\WhenTheLpaCanBeUsed;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToWhenTheLpaCanBeUsedTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToWhenTheLpaCanBeUsed $castToWhenTheLpaCanBeUsed;

    public function setUp(): void
    {
        $this->mockHydrator              = $this->createMock(ObjectMapper::class);
        $this->castToWhenTheLpaCanBeUsed = new CastToWhenTheLpaCanBeUsed();
    }

    #[Test]
    public function can_when_lpa_can_be_used(): void
    {
        $whenRegistered   = 'when registered';
        $whenLossCapacity = 'loss of capacity';

        $whenRegisteredResult   = $this->castToWhenTheLpaCanBeUsed->cast($whenRegistered, $this->mockHydrator);
        $whenLossCapacityResult = $this->castToWhenTheLpaCanBeUsed->cast($whenLossCapacity, $this->mockHydrator);
        $defaultResult          = $this->castToWhenTheLpaCanBeUsed->cast('', $this->mockHydrator);

        $this->assertEquals(WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY->value, $whenRegisteredResult);
        $this->assertEquals(WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST->value, $whenLossCapacityResult);
        $this->assertEquals('', $defaultResult);
    }

    #[Test]
    public function test_when_can_the_lpa_be_used_enum_has_issers(): void
    {
        $whenHasCapacity  = WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY;
        $whenCapacityLost = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST;
        $unknown          = WhenTheLpaCanBeUsed::UNKNOWN;

        $this->assertTrue($whenHasCapacity->isWhenHasCapacity());
        $this->assertFalse($whenHasCapacity->isUnknown());

        $this->assertTrue($whenCapacityLost->isWhenCapacityLost());
        $this->assertFalse($whenCapacityLost->isUnknown());

        $this->assertTrue($unknown->isUnknown());
        $this->assertFalse($unknown->isWhenCapacityLost());
    }
}
