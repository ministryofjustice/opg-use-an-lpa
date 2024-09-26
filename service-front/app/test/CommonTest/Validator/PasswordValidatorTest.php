<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Common\Validator\PasswordValidator;
use PHPUnit\Framework\TestCase;

class PasswordValidatorTest extends TestCase
{
    #[Test]
    public function it_passes_a_good_password(): void
    {
        $validator = new PasswordValidator();

        $this->assertTrue($validator->isValid('g0odPassword'));
        $this->assertCount(0, $validator->getMessages());
    }

    #[DataProvider('badPasswords')]
    #[Test]
    public function it_fails_bad_passwords(string $password, string $errorMessage): void
    {
        $validator = new PasswordValidator();

        $this->assertFalse($validator->isValid($password));
        $this->assertArrayHasKey($errorMessage, $validator->getMessages());
    }

    public static function badPasswords(): array
    {
        return [
            [
                'needsAdigit',
                PasswordValidator::MUST_INCLUDE_DIGIT,
            ],
            [
                'needsanuppercas3',
                PasswordValidator::MUST_INCLUDE_UPPER_CASE,
            ],
            [
                'NEED5ALOWERCAS3',
                PasswordValidator::MUST_INCLUDE_LOWER_CASE,
            ],
        ];
    }
}
