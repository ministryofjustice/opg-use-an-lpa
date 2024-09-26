<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\DateValidator;
use PHPUnit\Framework\TestCase;
use stdClass;

class DateValidatorTest extends TestCase
{
    private DateValidator $validator;

    public function setUp(): void
    {
        $this->validator = new DateValidator();
    }

    #[DataProvider('validFormatProvider')]
    public function testIsValidFormat($day, $month, $year): void
    {
        $value = [
            'day'   => $day,
            'month' => $month,
            'year'  => $year,
        ];

        $isValid = $this->validator->isValid($value);

        $this->assertTrue($isValid);
    }

    public static function validFormatProvider()
    {
        return [
            [1, 2, 1999],
            [2, 3, 1998],
            [28, 2, 1998],
            [29, 2, 2000],
            [25, 12, 1966],
        ];
    }

    #[DataProvider('notValidFormatProvider')]
    public function testIsValidNotValidFormat($day, $month, $year): void
    {
        $value = [];

        if (!is_null($day)) {
            $value['day'] = $day;
        }

        if (!is_null($month)) {
            $value['month'] = $month;
        }

        if (!is_null($year)) {
            $value['year'] = $year;
        }

        $isValid = $this->validator->isValid($value);

        $this->assertEquals([
            DateValidator::DATE_INVALID_FORMAT => 'Date value must be provided in an array',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public static function notValidFormatProvider()
    {
        return [
            [null, null, null],
            [1, null, null],
            [null, 2, null],
            [null, null, 1999],
            [1, 2, null],
            [null, 2, 1999],
            [1, null, 1999],
        ];
    }

    public function testIsValidEmptyFormat(): void
    {
        $isValid = $this->validator->isValid([
            'day'   => '',
            'month' => '',
            'year'  => '',
        ]);

        $this->assertEquals([
            DateValidator::DATE_EMPTY => 'Enter a date',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public function testIsValidIncompleteDayFormat(): void
    {
        $isValid = $this->validator->isValid([
            'day'   => '',
            'month' => 2,
            'year'  => 1999,
        ]);

        $this->assertEquals([
            DateValidator::DAY_INCOMPLETE => 'Date must include a day',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public function testIsValidIncompleteMonthFormat(): void
    {
        $isValid = $this->validator->isValid([
            'day'   => 2,
            'month' => '',
            'year'  => 1999,
        ]);

        $this->assertEquals([
            DateValidator::MONTH_INCOMPLETE => 'Date must include a month',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public function testIsValidIncompleteYearFormat(): void
    {
        $isValid = $this->validator->isValid([
            'day'   => 5,
            'month' => 2,
            'year'  => '',
        ]);

        $this->assertEquals([
            DateValidator::YEAR_INCOMPLETE => 'Date must include a year',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    #[DataProvider('invalidFormatProvider')]
    public function testIsValidInvalid($day, $month, $year): void
    {
        $value = [];

        if (!is_null($day)) {
            $value['day'] = $day;
        }

        if (!is_null($month)) {
            $value['month'] = $month;
        }

        if (!is_null($year)) {
            $value['year'] = $year;
        }

        $isValid = $this->validator->isValid($value);

        $this->assertEquals([
            DateValidator::DATE_INVALID => 'Enter a real date',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public static function invalidFormatProvider()
    {
        return [
            [-1, -2, -1999],
            ['honk', 'beep', 'peanuts'],
            [true, true, new stdClass()],
            [32, 2, 1999],
            [1, 13, 1999],
            [1, 2, 999],
            [29, 2, 1999],
        ];
    }
}
