<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\Equals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Equals::class)]
class EqualsTest extends TestCase
{
    #[DataProvider('firstNamesProvider')]
    #[Test]
    public function testFirstNames($expected, $a, $b): void
    {
        $this->assertEquals($expected, Equals::firstNames($a, $b));
    }

    public static function firstNamesProvider(): array
    {
        return [
            [
                false,
                'John',
                'Gohn',
            ],
            [
                true,
                'John',
                'John',
            ], // easy
            [
                true,
                'John',
                '  John  ',
            ], // trimmed
            [
                true,
                'John',
                'John James',
            ], // only first of firstnames checked
            [
                true,
                'O\'j\'o-h-n-a-t-h',
                "O’j‘o\u{2010}h\u{2011}n\u{2012}a\u{2013}t\u{2014}h",
            ], // normalises quotes and dashes to ' and -
            [
                true,
                'JOHN',
                'jOhn',
            ], // ignores case
        ];
    }

    #[DataProvider('lastNameProvider')]
    #[Test]
    public function testLastName(bool $expected, string $a, string $b): void
    {
        $this->assertEquals($expected, Equals::lastName($a, $b));
    }

    public static function lastNameProvider(): array
    {
        return [
            [
                false,
                'John',
                'Gohn',
            ],
            [
                true,
                'John',
                'John',
            ], // easy
            [
                true,
                'John',
                '  John  ',
            ], // trimmed
            [
                false,
                'John',
                'John James',
            ], // all names checked
            [
                true,
                'John James Gary',
                'John    James   	  Gary',
            ], // all names checked with multiple spaces collapsed
            [
                true,
                'O\'john',
                'O’john',
            ], // normalises ’ to '
            [
                true,
                'JOHN',
                'jOhn',
            ], // ignores case
        ];
    }

    #[DataProvider('postcodeProvider')]
    #[Test]
    public function testPostcode(bool $expected, string $a, string $b): void
    {
        $this->assertEquals($expected, Equals::postcode($a, $b));
    }

    public static function postcodeProvider(): array
    {
        return [
            [
                false,
                'F11FF',
                'F12FF',
            ],
            [
                true,
                'F11FF',
                'F11FF',
            ], // easy
            [
                true,
                'F11FF',
                '  F1 1FF  ',
            ], // ignores spaces
            [
                true,
                'F11FF',
                'f11ff',
            ], // ignores case
        ];
    }
}
