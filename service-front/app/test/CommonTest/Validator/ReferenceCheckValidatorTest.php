<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\ReferenceCheckValidator;
use PHPUnit\Framework\TestCase;

class ReferenceCheckValidatorTest extends TestCase
{
    /**
     * @var ReferenceCheckValidator
     */
    private $validator;

    public function setUp(): void
    {
        $this->validator = new ReferenceCheckValidator();
    }

    /**
     * @dataProvider validFormatProvider
     */
    public function testIsValidReference($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertTrue($isValid);
    }

    public function validFormatProvider()
    {
        return [
            ['700000000252'],
            ['700000000047'],
            ['700000000138'],
            ['712121234926'],
            ['2121267'],
            ['2089183'],
            ['3089166'],
            ['3004918']
        ];
    }

    /**
     * @dataProvider notValidMerisFormatProvider
     */
    public function testMerisReferenceStartsWithSeven($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                ReferenceCheckValidator::MERIS_NO_MUST_START_WITH =>
                    'LPA reference numbers that are 7 numbers long must begin with a 2 or 3',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public function notValidMerisFormatProvider()
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

    /**
     * @dataProvider invalidLengthProvider
     */
    public function testIsValidInvalid($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                ReferenceCheckValidator::MUST_BE_LENGTH =>
                    'Enter an LPA reference number that is either 7 or 12 numbers long',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public function invalidLengthProvider()
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
