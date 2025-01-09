<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToCaseSubtype;
use EventSauce\ObjectHydrator\ObjectMapper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToCaseSubtypeTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToCaseSubtype $castToCaseSubtype;

    public function setUp(): void
    {
        $this->mockHydrator      = $this->createMock(ObjectMapper::class);
        $this->castToCaseSubtype = new CastToCaseSubtype();
    }

    #[DataProvider('caseSubtypeProvider')]
    #[Test]
    public function can_cast_case_subtype($caseSubType, $expectedCaseSubType): void
    {
        $result = $this->castToCaseSubtype->cast($caseSubType, $this->mockHydrator);

        $this->assertEquals($expectedCaseSubType, $result);
    }

    #[Test]
    public function throws_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid shorthand name: invalid-value');

        $this->castToCaseSubtype->cast('invalid-value', $this->mockHydrator);
    }

    public static function caseSubtypeProvider(): array
    {
        return [
            [
                'personal-welfare',
                'hw',
            ],
            [
                'property-and-affairs',
                'pfa',
            ],
        ];
    }
}
