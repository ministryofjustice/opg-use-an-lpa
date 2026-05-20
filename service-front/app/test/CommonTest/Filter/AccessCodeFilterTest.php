<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\AccessCodeFilter;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AccessCodeFilterTest extends TestCase
{
    use ProphecyTrait;

    private AccessCodeFilter $filter;

    public function setUp(): void
    {
        $this->filter = new AccessCodeFilter();
    }

    #[Test]
    public function it_expects_a_string_input(): void
    {
        $this->expectException(Exception::class);
        $this->filter->filter(12);
    }

    #[Test]
    #[DataProvider('codeFormatProvider')]
    public function removesPrefixAndHyphensAndWhitespace(string $code, string $expected): void
    {
        $formattedCode = $this->filter->filter($code);
        $this->assertEquals($expected, $formattedCode);
    }

    public static function codeFormatProvider(): array
    {
        return [
            [
                'V-ABCD-1234-EFGH',
                'ABCD1234EFGH',
            ],
            [
                'v-abCd-1234-EfgH',
                'ABCD1234EFGH',
            ],
            [
                'V abcd 1234 efgh',
                'ABCD1234EFGH',
            ],
            [
                'V - ABCD - 1234 - EFGH',
                'ABCD1234EFGH',
            ],
            [
                'V   ABCD   1234   EFGH',
                'ABCD1234EFGH',
            ],
            [
                'v--ABCD--1234--EFGH',
                'ABCD1234EFGH',
            ],
            [
                'V$ABCD[]1234()EFGH',
                'ABCD1234EFGH',
            ],
            [
                'Q$ABCD[]1234()EFGH',
                'QABCD1234EFGH',
            ],
        ];
    }
}
