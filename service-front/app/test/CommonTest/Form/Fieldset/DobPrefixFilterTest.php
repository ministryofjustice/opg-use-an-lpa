<?php

declare(strict_types=1);

namespace CommonTest\Form\Fieldset;

use Common\Form\Fieldset\DatePrefixFilter;
use PHPUnit\Framework\TestCase;

class DobPrefixFilterTest extends TestCase
{
    /**
     * @var DatePrefixFilter
     */
    private DatePrefixFilter $filter;

    public function setUp(): void
    {
        $this->filter = new DatePrefixFilter();
    }

    /**
     * @dataProvider validFormatProvider
     */
    public function testIsDobFormattedWithLeadingZeroes(array $expected, array $dob)
    {
        $formattedDate = $this->filter->filter($expected);
        $this->assertEquals($formattedDate, $dob);
    }

    public function validFormatProvider() : array
    {
        return [
            [
                [
                    'day' => '1',
                    'month' => '10',
                    'year' => '1980',
                ],
                [
                    'day' => '01',
                    'month' => '10',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => '11',
                    'month' => '8',
                    'year' => '1980',
                ],
                [
                    'day' => '11',
                    'month' => '08',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => '1',
                    'month' => '1',
                    'year' => '1980',
                ],
                [
                    'day' => '01',
                    'month' => '01',
                    'year' => '1980',
                ]
            ],
            [
                [
                    'day' => '11',
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
