<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\LuhnCheck;
use PHPUnit\Framework\TestCase;

class LuhnCheckTest extends TestCase
{
    /**
     * @var LuhnCheck
     */
    private $validator;

    public function setUp(): void
    {
        $this->validator = new LuhnCheck();
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
            ['322271628'],
            ['4417123456789113']
        ];
    }

    /**
     * @dataProvider notValidFormatProvider
     */
    public function testIsValidNotValidFormat($reference_number)
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals([
                                LuhnCheck::INVALID_REFERENCE => 'The LPA reference number provided is not correct',
                            ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public function notValidFormatProvider()
    {
        return [
            ['700000000132'],
            ['700000000254'],
            ['711111111526'],
        ];
    }
}
