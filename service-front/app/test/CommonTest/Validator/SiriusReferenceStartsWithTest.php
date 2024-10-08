<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\SiriusReferenceStartsWithCheck;
use PHPUnit\Framework\TestCase;

class SiriusReferenceStartsWithTest extends TestCase
{
    private SiriusReferenceStartsWithCheck $validator;

    public function setUp(): void
    {
        $this->validator = new SiriusReferenceStartsWithCheck();
    }

    #[DataProvider('validFormatProvider')]
    public function testIsValidReference($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertTrue($isValid);
    }

    public static function validFormatProvider()
    {
        return [
            ['700000000252'],
            ['700000000047'],
            ['700000000138'],
            ['712121234926'],
        ];
    }

    #[DataProvider('notValidMerisFormatProvider')]
    public function testMerisReferenceStartsWithSeven($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                SiriusReferenceStartsWithCheck::LPA_MUST_START_WITH
                    => 'LPA reference numbers that are 12 numbers long must begin with a 7',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public static function notValidMerisFormatProvider()
    {
        return [
            ['100000000132'],
            ['200000000254'],
            ['311111111526'],
        ];
    }
}
