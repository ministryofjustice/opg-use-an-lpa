<?php

declare(strict_types=1);

namespace CommonTest\Filter;

use Common\Filter\ConvertQuotesToApostrophe;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ConvertQuotesToApostropheTest extends TestCase
{
    use ProphecyTrait;

    private ConvertQuotesToApostrophe $filter;

    public function setUp(): void
    {
        $this->filter = new ConvertQuotesToApostrophe();
    }

    #[Test]
    public function it_expects_a_string_input(): void
    {
        $this->expectException(Exception::class);
        $this->filter->filter(12);
    }

    #[Test]
    #[DataProvider('nameFormatProvider')]
    public function convertQuotesToApostrophe(string $name, string $expected): void
    {
        $formattedName = $this->filter->filter($name);
        $this->assertEquals($expected, $formattedName);
    }

    public static function nameFormatProvider(): array
    {
        return [
            ['Babara’s', 'Babara\'s'],
            ['Jones’s', 'Jones\'s'],
            ['Swyddfa’r', 'Swyddfa\'r'],
            ['D’Andre', 'D\'Andre'],
            ['d’Antoine', 'd\'Antoine'],
        ];
    }
}
