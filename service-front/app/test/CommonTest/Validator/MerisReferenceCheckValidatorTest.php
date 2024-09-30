<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\MerisReferenceCheckValidator;
use PHPUnit\Framework\TestCase;

class MerisReferenceCheckValidatorTest extends TestCase
{
    private MerisReferenceCheckValidator $validator;

    public function setUp(): void
    {
        $this->validator = new MerisReferenceCheckValidator();
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
            ['2121267'],
            ['2089183'],
            ['3089166'],
            ['3004918'],
        ];
    }

    #[DataProvider('notValidMerisFormatProvider')]
    public function testMerisReferenceStartsWithTwoOrThree($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                MerisReferenceCheckValidator::MERIS_NO_MUST_START_WITH
                    => 'LPA reference numbers that are 7 numbers long must begin with a 2 or 3',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public static function notValidMerisFormatProvider()
    {
        return [
            ['5004919'],
            ['4121266'],
            ['6000009'],
            ['7000009'],
            ['8000000'],
            ['1000000'],
            ['9111111'],
        ];
    }

    #[DataProvider('invalidLengthProvider')]
    public function testIsValidInvalid($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                MerisReferenceCheckValidator::MUST_BE_LENGTH
                    => 'Enter an LPA reference number that is either 7 or 12 numbers long',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public static function invalidLengthProvider()
    {
        return [
            ['70000000025432'],
            ['1234567891234'],
            ['21111117'],
            ['311111173'],
            ['7000000002521'],
            ['212126'],
            ['222'],
        ];
    }
}
