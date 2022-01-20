<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\MerisCheckValidator;
use PHPUnit\Framework\TestCase;

class MerisCheckValidatorTest extends TestCase
{
    /**
     * @var MerisCheckValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new MerisCheckValidator();
    }

    /**
     * @dataProvider validFormatProvider
     */
    public function testIsValidReference($reference_number)
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
     * @dataProvider notValidStartFormatProvider
     */
    public function testLpaReferenceStartsWithSeven($reference_number)
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                MerisCheckValidator::LPA_MUST_START_WITH =>
                    'LPA reference numbers that are 12 numbers long must begin with a 7',
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    public function notValidStartFormatProvider()
    {
        return [
            ['400000000132'],
            ['500000000254'],
            ['123456789123'],
            ['234987456123'],
            ['800000012345'],
            ['100000000002'],
            ['911111111119'],
        ];
    }

    /**
     * @dataProvider notValidMerisFormatProvider
     */
    public function testMerisReferenceStartsWithSeven($reference_number)
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                MerisCheckValidator::MERIS_NO_MUST_START_WITH =>
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
    public function testIsValidInvalid($reference_number)
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals(
            [
                MerisCheckValidator::MUST_BE_LENGTH =>
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
