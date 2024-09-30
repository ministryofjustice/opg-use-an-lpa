<?php

declare(strict_types=1);

namespace CommonTest\Form\Fieldset;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Form\Fieldset\DateTrimFilter;
use PHPUnit\Framework\TestCase;

class DobTrimFilterTest extends TestCase
{
    private DateTrimFilter $filter;

    public function setUp(): void
    {
        $this->filter = new DateTrimFilter();
    }

    #[DataProvider('validFormatProvider')]
    public function testIsDobTrimmed(array $expected, array $dob): void
    {
        $formattedDate = $this->filter->filter($expected);
        $this->assertEquals($formattedDate, $dob);
    }

    public static function validFormatProvider(): array
    {
        return [
            [
                [
                    'day'   => ' 1',
                    'month' => ' 10',
                    'year'  => ' 1980',
                ],
                [
                    'day'   => '1',
                    'month' => '10',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => '11 ',
                    'month' => '8 ',
                    'year'  => '1980 ',
                ],
                [
                    'day'   => '11',
                    'month' => '8',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => ' 1 ',
                    'month' => ' 1 ',
                    'year'  => ' 1980 ',
                ],
                [
                    'day'   => '1',
                    'month' => '1',
                    'year'  => '1980',
                ],
            ],
            [
                [
                    'day'   => ' 11',
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
