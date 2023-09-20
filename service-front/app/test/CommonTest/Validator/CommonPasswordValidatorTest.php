<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\CommonPasswordValidator;
use PHPUnit\Framework\TestCase;

class CommonPasswordValidatorTest extends TestCase
{
    const PW_FILE_PATH = '/tmp/commonpasswords.txt';
    const PWNED_PW_URL = 'https://www.ncsc.gov.uk/static-assets/documents/PwnedPasswordsTop100k.txt';

    public function setUp(): void
    {
        file_put_contents(
            self::PW_FILE_PATH,
            fopen(self::PWNED_PW_URL, 'r')
        );

        $this->validator = new CommonPasswordValidator();
    }

    /**
     * Verify a constraint message is triggered when value is invalid.
     */
    public function testValidateOnInvalid()
    {
        $this->assertFalse($this->validator->isValid('Password123'));
        print(count($this->validator->getMessages()));
        $this->assertArrayHasKey(CommonPasswordValidator::COMMON_PASSWORD, $this->validator->getMessages());
    }


    /**
     * Verify no constraint message is triggered when value is valid.
     */
    public function testValidateOnValid()
    {
        $this->assertTrue($this->validator->isValid('Aformidablepw876!'));
        $this->assertCount(0, $this->validator->getMessages());

    }
}