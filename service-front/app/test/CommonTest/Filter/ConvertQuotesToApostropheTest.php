<?php

namespace CommonTest\Filter;

use Common\Filter\ConvertQuotesToApostrophe;
use PHPUnit\Framework\TestCase;

class ConvertQuotesToApostropheTest extends TestCase
{
    private ConvertQuotesToApostrophe $filter;

    public function setUp()
    {
        $this->filter = new ConvertQuotesToApostrophe();
    }

    /**
     * @dataProvider nameFormatProvider
     */
    public function testConvertQuotesToApostrophe(string $name, string $expected)
    {
        $formattedName = $this->filter->filter($name);
        $this->assertEquals($expected, $formattedName);
    }

    public function nameFormatProvider(): array
    {
        return [
            ['Babara’s', 'Babara\'s'],
            ['Jones’s', 'Jones\'s'],
            ['Swyddfa’r', 'Swyddfa\'r'],
            ['D’Andre', 'D\'Andre'],
            ['d’Antoine', 'd\'Antoine']
        ];
    }
}
