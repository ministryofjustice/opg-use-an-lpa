<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\StripSpacesAndHyphens;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class StripSpacesAndHyphensTest extends TestCase
{
    use ProphecyTrait;

    private StripSpacesAndHyphens $filter;

    public function setUp(): void
    {
        $this->filter = new StripSpacesAndHyphens();
    }

    #[Test]
    public function it_expects_a_string_input(): void
    {
        $this->expectException(Exception::class);
        $this->filter->filter(12);
    }

    #[Test]
    #[DataProvider('codeFormatProvider')]
    public function removesHyphensAndWhitespace(string $code, string $expected): void
    {
        $formattedCode = $this->filter->filter($code);
        $this->assertEquals($expected, $formattedCode);
    }

    public static function codeFormatProvider(): array
    {
        return [
            ['7000 1001 0001', '700010010001'],
            ['7000-1001-0001', '700010010001'],
            ['7000 - 1001 - 0001', '700010010001'],
            ['7000   1001   0001', '700010010001'],
            ['7000---1001---0001', '700010010001'],
            ['700010010001', '700010010001'],
            ['---7000---1001---0001   ', '700010010001'],
            ['7000–1001–0001', '700010010001'],
            ['7000—1001—0001', '700010010001'],
            ['7000–1001—0001', '700010010001'],
            ['7000——1001——0001', '700010010001'],
        ];
    }
}
