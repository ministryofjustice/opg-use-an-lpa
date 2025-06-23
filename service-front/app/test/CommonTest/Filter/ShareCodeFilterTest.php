<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\ShareCodeFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ShareCodeFilterTest extends TestCase
{
    use ProphecyTrait;

    private ShareCodeFilter $filter;

    public function setUp(): void
    {
        $this->filter = new ShareCodeFilter();
    }

    #[DataProvider('codeFormatProvider')]
    public function testRemovesPrefixAndHyphensAndWhitespace(string $code, string $expected): void
    {
        $formattedCode = $this->filter->filter($code);
        $this->assertEquals($expected, $formattedCode);
    }

    public static function codeFormatProvider(): array
    {
        return [
            ['V-ABCD-1234-EFGH', 'ABCD1234EFGH'],
            ['v-abCd-1234-EfgH', 'ABCD1234EFGH'],
            ['V abcd 1234 efgh', 'ABCD1234EFGH'],
            ['V - ABCD - 1234 - EFGH', 'ABCD1234EFGH'],
            ['V   ABCD   1234   EFGH', 'ABCD1234EFGH'],
            ['v--ABCD--1234--EFGH', 'ABCD1234EFGH'],
            ['P-AB12-CD34-EF56-G7', 'P-AB12-CD34-EF56-G7'],
            ['P-ab12-CD34-ef56-G7', 'P-AB12-CD34-EF56-G7'],
            ['P AB12 CD34 EF56 G7', 'P-AB12-CD34-EF56-G7'],
            ['P   AB12   CD34   EF56   G7', 'P-AB12-CD34-EF56-G7'],
            ['P - AB12 - CD34 - EF56 - G7', 'P-AB12-CD34-EF56-G7'],
            ['P--AB12--CD34--EF56--G7', 'P-AB12-CD34-EF56-G7'],
        ];
    }
}
