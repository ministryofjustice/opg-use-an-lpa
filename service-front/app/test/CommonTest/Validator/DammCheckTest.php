<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\DammCheck;
use PHPUnit\Framework\TestCase;

class DammCheckTest extends TestCase
{
    private DammCheck $validator;

    public function setUp(): void
    {
        $this->validator = new DammCheck();
    }

    #[DataProvider('validFormatProvider')]
    public function testIsValidReference($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertTrue($isValid);
    }

    public static function validFormatProvider(): array
    {
        return [
            ['M-1234-5678-9018'],
            ['M-1098-7654-3214'],
            ['M-5649-8734-8960'],
        ];
    }

    #[DataProvider('notValidFormatProvider')]
    public function testIsValidNotValidFormat($reference_number): void
    {
        $isValid = $this->validator->isValid($reference_number);

        $this->assertEquals([
            DammCheck::INVALID_REFERENCE => 'The LPA reference number provided is not correct',
        ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }

    public static function notValidFormatProvider(): array
    {
        return [
            ['X-1234-5678-9018'],
            ['M-1234-5678-901X'],
            ['M-1234-5678-9017'],
            ['M-1234-5678-9019'],
            ['M-1098-7654-3213'],
            ['M-5649-8734-8961'],
        ];
    }
}
