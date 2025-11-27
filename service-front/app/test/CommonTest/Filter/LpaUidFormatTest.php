<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\LpaUidFormat;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LpaUidFormatTest extends TestCase
{
    use ProphecyTrait;

    private LpaUidFormat $filter;

    public function setUp(): void
    {
        $this->filter = new LpaUidFormat();
    }

    #[Test]
    public function it_expects_a_string_input(): void
    {
        $this->expectException(Exception::class);
        $this->filter->filter(12);
    }

    #[Test]
    #[DataProvider('lpaUidFormatProvider')]
    public function removesHyphensAndWhitespace(string $lpaUid, string $expected): void
    {
        $formattedCode = $this->filter->filter($lpaUid);
        $this->assertEquals($expected, $formattedCode);
    }

    public static function lpaUidFormatProvider(): array
    {
        return [
            ['700010010001', '700010010001'],
            ['7000-1001-0001', '7000-1001-0001'],
            ['m123456789018', 'M-1234-5678-9018'],
            ['m12345678901', 'm12345678901'],
            ['M123456789018', 'M-1234-5678-9018'],
            ['M-123456789018', 'M-123456789018'],
        ];
    }
}
