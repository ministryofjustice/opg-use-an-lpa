<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Actor\Validator\OptionSelectedValidator;
use PHPUnit\Framework\TestCase;
use DateTime;

class OptionSelectedValidatorTest extends TestCase
{

    private OptionSelectedValidator $validator;

    public function setUp()
    {
        $this->validator = new OptionSelectedValidator();
    }

    /** @test */
    public function testOptionIsPresent()
    {
        $value = [
            'telephone' => '0123456789',
            'no_phone' => 'true'
        ];

        $isValid = $this->validator->isValid($value);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function testErrorWhenNotPresent()
    {
        $value = [];

        $isValid = $this->validator->isValid($value);

        $this->assertEquals([
            OptionSelectedValidator::OPTION_MUST_BE_SELECTED =>
                OptionSelectedValidator::OPTION_MUST_BE_SELECTED_MESSAGE
                            ], $this->validator->getMessages());

        $this->assertFalse($isValid);
    }
}
