<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToCaseSubtype;
use EventSauce\ObjectHydrator\ObjectMapper;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CastToCaseSubtypeTest extends TestCase
{
    private MockObject $mockHydrator;

    private CastToCaseSubtype $castToCaseSubtype;

    protected function setUp(): void
    {
        $this->mockHydrator      = $this->createMock(ObjectMapper::class);
        $this->castToCaseSubtype = new CastToCaseSubtype();
    }

    #[DataProvider('caseSubtypeProvider')]
    #[Test]
    public function can_cast_case_subtype(string $caseSubType, string $expectedCaseSubType): void
    {
        $result = $this->castToCaseSubtype->cast($caseSubType, $this->mockHydrator);
        $this->assertSame($expectedCaseSubType, $result);
    }

    #[Test]
    public function throws_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid shorthand name: invalid-value');

        $this->castToCaseSubtype->cast('invalid-value', $this->mockHydrator);
    }

    public static function caseSubtypeProvider(): Iterator
    {
        yield [
            'personal-welfare',
            'hw',
        ];
        yield [
            'property-and-affairs',
            'pfa',
        ];
    }
}
