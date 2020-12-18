<?php

namespace CommonTest\Filter;

use Common\Filter\StripSpacesAndHyphens;
use PHPUnit\Framework\TestCase;

class StripSpacesAndHyphensTest extends TestCase
{
    private StripSpacesAndHyphens $filter;

    public function setUp()
    {
        $this->filter = new StripSpacesAndHyphens();
    }

    /**
     * @dataProvider codeFormatProvider
     */
    public function testRemovesHyphensAndWhitespace(string $code, string $expected)
    {
        $formattedCode = $this->filter->filter($code);
        $this->assertEquals($expected, $formattedCode);
    }

    public function codeFormatProvider(): array
    {
        return [
            ['7000 1001 0001', '700010010001'],
            ['7000-1001-0001', '700010010001'],
            ['7000 - 1001 - 0001', '700010010001'],
            ['7000   1001   0001', '700010010001'],
            ['7000---1001---0001', '700010010001'],
            ['700010010001', '700010010001'],
            ['---7000---1001---0001   ', '700010010001']
        ];
    }
}
