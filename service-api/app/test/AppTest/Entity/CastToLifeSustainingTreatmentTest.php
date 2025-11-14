<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToLifeSustainingTreatment;
use EventSauce\ObjectHydrator\ObjectMapper;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToLifeSustainingTreatmentTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToLifeSustainingTreatment $castToLifeSustainingTreatment;

    public function setUp(): void
    {
        $this->mockHydrator                  = $this->createMock(ObjectMapper::class);
        $this->castToLifeSustainingTreatment = new CastToLifeSustainingTreatment();
    }

    #[DataProvider('lifeSustainingTreatmentProvider')]
    #[Test]
    public function can_cast_life_sustaining_treatment($lifeSustainingTreatment, $expectedLifeSustainingTreatment): void
    {
        $result = $this->castToLifeSustainingTreatment->cast($lifeSustainingTreatment, $this->mockHydrator);

        $this->assertEquals($expectedLifeSustainingTreatment, $result);
    }

    #[Test]
    public function throws_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid shorthand name: invalid-value');

        $this->castToLifeSustainingTreatment->cast('invalid-value', $this->mockHydrator);
    }

    public static function lifeSustainingTreatmentProvider(): Iterator
    {
        yield [
            'Option A',
            'option-a',
        ];
        yield [
            'Option B',
            'option-b',
        ];
    }
}
