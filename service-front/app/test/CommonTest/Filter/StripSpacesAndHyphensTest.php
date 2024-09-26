<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Filter\StripSpacesAndHyphens;
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

    #[DataProvider('codeFormatProvider')]
    public function testRemovesHyphensAndWhitespace(string $code, string $expected): void
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
