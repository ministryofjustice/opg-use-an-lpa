<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\ActorViewerCodeFilter;
use PHPUnit\Framework\TestCase;

class ActorViewerCodeFilterTest extends TestCase
{
    private ActorViewerCodeFilter $filter;

    public function setUp(): void
    {
        $this->filter = new ActorViewerCodeFilter();
    }

    /**
     * @dataProvider codeFormatProvider
     */
    public function testRemovesPrefixAndHyphensAndWhitespace(string $code, string $expected)
    {
        $formattedCode = $this->filter->filter($code);
        $this->assertEquals($expected, $formattedCode);
    }

    public function codeFormatProvider(): array
    {
        return [
            ['V-ABCD-1234-EFGH', 'ABCD1234EFGH'],
            ['v-abCd-1234-EfgH', 'ABCD1234EFGH'],
            ['V abcd 1234 efgh', 'ABCD1234EFGH'],
            ['V - ABCD - 1234 - EFGH', 'ABCD1234EFGH'],
            ['V   ABCD   1234   EFGH', 'ABCD1234EFGH'],
            ['v--ABCD--1234--EFGH', 'ABCD1234EFGH'],
            ['C-ABCD-1234-EFGH', 'ABCD1234EFGH'],
            ['c-abCd-1234-EfgH', 'ABCD1234EFGH'],
            ['C abcd 1234 efgh', 'ABCD1234EFGH'],
            ['C   abcd   1234   efgh', 'ABCD1234EFGH'],
            ['C - ABCD - 1234 - EFGH', 'ABCD1234EFGH'],
            ['c--ABCD--1234--EFGH', 'ABCD1234EFGH']
        ];
    }
}
