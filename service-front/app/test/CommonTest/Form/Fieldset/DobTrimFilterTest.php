<?php

declare(strict_types=1);

namespace CommonTest\Form\Fieldset;

use Common\Form\Fieldset\DateTrimFilter;
use PHPUnit\Framework\TestCase;

class DobTrimFilterTest extends TestCase
{
    /**
     * @var DateTrimFilter
     */
    private DateTrimFilter $filter;

    public function setUp(): void
    {
        $this->filter = new DateTrimFilter();
    }

    /**
     * @dataProvider validFormatProvider
     * @param array $expected
     * @param array $dob
     */
    public function testIsDobTrimmed(array $expected, array $dob)
    {
        $formattedDate = $this->filter->filter($expected);
        $this->assertEquals($formattedDate, $dob);
    }

    public function validFormatProvider(): array
    {
        return [
            [
                [
                    'day' => ' 1',
                    'month' => ' 10',
                    'year' => ' 1980',
                ],
                [
                    'day' => '1',
                    'month' => '10',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => '11 ',
                    'month' => '8 ',
                    'year' => '1980 ',
                ],
                [
                    'day' => '11',
                    'month' => '8',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => ' 1 ',
                    'month' => ' 1 ',
                    'year' => ' 1980 ',
                ],
                [
                    'day' => '1',
                    'month' => '1',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => ' 11',
                    'month' => '10',
                    'year' => '1980',
                ],
                [
                    'day' => '11',
                    'month' => '10',
                    'year' => '1980',
                ]
            ],
        ];
    }
}
