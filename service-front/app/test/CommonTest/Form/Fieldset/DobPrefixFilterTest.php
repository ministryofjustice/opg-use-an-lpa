<?php

declare(strict_types=1);

namespace CommonTest\Form\Fieldset;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Form\Fieldset\DatePrefixFilter;
use PHPUnit\Framework\TestCase;

class DobPrefixFilterTest extends TestCase
{
    private DatePrefixFilter $filter;

    public function setUp(): void
    {
        $this->filter = new DatePrefixFilter();
    }

    #[DataProvider('validFormatProvider')]
    public function testIsDobFormattedWithLeadingZeroes(array $expected, array $dob): void
    {
        $formattedDate = $this->filter->filter($expected);
        $this->assertEquals($formattedDate, $dob);
    }

    public static function validFormatProvider(): array
    {
        return [
            [
                [
                    'day'   => '1',
                    'month' => '10',
                    'year'  => '1980',
                ],
                [
                    'day'   => '01',
                    'month' => '10',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => '11',
                    'month' => '8',
                    'year'  => '1980',
                ],
                [
                    'day'   => '11',
                    'month' => '08',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => '1',
                    'month' => '1',
                    'year'  => '1980',
                ],
                [
                    'day'   => '01',
                    'month' => '01',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => '11',
                    'month' => '10',
                    'year'  => '1980',
                ],
                [
                    'day'   => '11',
                    'month' => '10',
                    'year'  => '1980',
                ],
            ],
        ];
    }
}
