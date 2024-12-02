<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToLifeSustainingTreatment;
use EventSauce\ObjectHydrator\ObjectMapper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ValueError;

class CastToLifeSustainingTreatmentTest extends TestCase
{
    use ProphecyTrait;

    private ObjectMapper $mockHydrator;
    private CastToLifeSustainingTreatment $caster;

    public function setUp(): void
    {
        $this->mockHydrator = $this->createMock(ObjectMapper::class);
        $this->caster       = new CastToLifeSustainingTreatment();
    }

    #[Test]
    #[DataProvider('lifeSustainingTreatmentProvider')]
    public function can_cast_life_sustaining_treatment($input, $expectedOutput): void
    {
        $this->assertEquals(
            $expectedOutput,
            $this->caster->cast(
                $input,
                $this->prophesize(ObjectMapper::class)->reveal()
            )
        );
    }

    public function lifeSustainingTreatmentProvider(): array
    {
        return [
            ['Option A', 'option-a'],
            ['option-a', 'option-a'],
            ['Option B', 'option-b'],
            ['option-b', 'option-b'],
        ];
    }

    #[Test]
    public function throws_exception_on_invalid_type()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->caster->cast(
            'not-a-valid-type',
            $this->prophesize(ObjectMapper::class)->reveal()
        );
    }
}
