<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\DobValidator;
use DateTime;
use PHPUnit\Framework\TestCase;

class DobValidatorTest extends TestCase
{
    /**
     * @var DobValidator
     */
    private $validator;

    public function setUp(): void
    {
        $this->validator = new DobValidator();
    }

    /**
     * @dataProvider validFormatProvider
     */
    public function testIsValidFormat($day, $month, $year)
    {
        $value = [
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ];

        $isValid = $this->validator->isValid($value);

        $this->assertTrue($isValid);
    }

    public function validFormatProvider()
    {
        return [
            [1, 2, 1999],
            [2, 3, 1998],
            [28, 2, 1998],
            [29, 2, 2000],
            [25, 12, 1966],
        ];
    }

    public function testIsValidFutureDate()
    {
        $now = new DateTime();
        $now->modify('+1 day');

        $isValid = $this->validator->isValid([
            'day'   => $now->format('j'),
            'month' => $now->format('n'),
            'year'  => $now->format('Y'),
        ]);

        $this->assertEquals([
            DobValidator::AGE_NEGATIVE => 'Date of birth must be in the past',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public function testIsValidTooYoung()
    {
        $now = new DateTime();
        $now->modify('-1 day');

        $isValid = $this->validator->isValid([
            'day'   => $now->format('j'),
            'month' => $now->format('n'),
            'year'  => $now->format('Y'),
        ]);

        $this->assertEquals([
            DobValidator::AGE_TOO_YOUNG => 'Check your date of birth is correct - you cannot be an attorney or donor if you’re under 18',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }
}
