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
            ['M123456789018'],
            ['m123456789018'],
            ['M109876543214'],
            ['M564987348960'],
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
            ['X123456789018'],
            ['M12345678901X'],
            ['M123456789017'],
            ['M123456789019'],
            ['M109876543213'],
            ['M564987348961'],
        ];
    }
}
