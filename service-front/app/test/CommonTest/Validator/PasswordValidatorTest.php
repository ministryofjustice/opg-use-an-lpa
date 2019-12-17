<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\PasswordValidator;
use PHPUnit\Framework\TestCase;

class PasswordValidatorTest extends TestCase
{
    /** @test */
    public function it_passes_a_good_password() {
        $validator = new PasswordValidator();

        $this->assertTrue($validator->isValid('g0odPassword'));
        $this->assertCount(0, $validator->getMessages());
    }

    /**
     * @test
     * @dataProvider badPasswords
     */
    public function it_fails_bad_passwords(string $password, string $errorMessage)
    {
        $validator = new PasswordValidator();

        $this->assertFalse($validator->isValid($password));
        $this->assertArrayHasKey($errorMessage, $validator->getMessages());
    }

    public function badPasswords(): array
    {
        return [
            [
                'needsAdigit',
                PasswordValidator::MUST_INCLUDE_DIGIT
            ],
            [
                'needsanuppercas3',
                PasswordValidator::MUST_INCLUDE_UPPER_CASE
            ],
            [
                'NEED5ALOWERCAS3',
                PasswordValidator::MUST_INCLUDE_LOWER_CASE
            ]
        ];
    }
}
