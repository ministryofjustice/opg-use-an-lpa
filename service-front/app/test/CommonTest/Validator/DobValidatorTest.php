<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\DobValidator;
use DateTime;
use PHPUnit\Framework\TestCase;

class DobValidatorTest extends TestCase
{
    private DobValidator $validator;

    public function setUp(): void
    {
        $this->validator = new DobValidator();
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

    public function testIsValidFutureDate(): void
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

    public function testIsValidTooYoung(): void
    {
        $now = new DateTime();
        $now->modify('-1 day');

        $isValid = $this->validator->isValid([
            'day'   => $now->format('j'),
            'month' => $now->format('n'),
            'year'  => $now->format('Y'),
        ]);

        $this->assertEquals([
            DobValidator::AGE_TOO_YOUNG
                => 'Check your date of birth is correct - you cannot be an attorney or donor if you’re under 18',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }
}
