<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\OptionSelectedValidator;
use PHPUnit\Framework\TestCase;

class OptionSelectedValidatorTest extends TestCase
{
    private OptionSelectedValidator $validator;

    public function setUp(): void
    {
        $this->validator = new OptionSelectedValidator();
    }

    /** @test */
    public function isValidWhenOnlyTelephoneNumberPresent(): void
    {
        $isValid = $this->validator->isValid(
            [
                'telephone' => '0123456789'
            ]
        );

        $this->assertTrue($isValid);
    }

    /** @test */
    public function isValidWhenOnlyNoPhoneCheckboxPresent(): void
    {
        $isValid = $this->validator->isValid(
            [
                'telephone' => '',
                'no_phone'  => 'yes'
            ]
        );

        $this->assertTrue($isValid);
    }

    /** @test */
    public function isNotValidWhenNeitherValuesArePresent(): void
    {
        $isValid = $this->validator->isValid([]);

        $this->assertEquals(
            [
                OptionSelectedValidator::OPTION_MUST_BE_SELECTED =>
                    'Either enter your phone number or check the box to say you cannot take calls'
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }

    /** @test */
    public function isNotValidWhenBothValuesArePresent(): void
    {
        $isValid = $this->validator->isValid(
            [
                'telephone' => '0123456789',
                'no_phone' => 'yes'
            ]
        );

        $this->assertEquals(
            [
                OptionSelectedValidator::OPTION_MUST_BE_SELECTED =>
                    'Either enter your phone number or check the box to say you cannot take calls'
            ],
            $this->validator->getMessages()
        );

        $this->assertFalse($isValid);
    }
}
