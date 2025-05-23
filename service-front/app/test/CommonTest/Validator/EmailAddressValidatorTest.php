<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use PHPUnit\Framework\Attributes\Test;
use Common\Validator\EmailAddressValidator;
use PHPUnit\Framework\TestCase;

class EmailAddressValidatorTest extends TestCase
{
    #[Test]
    public function correctly_validates_known_good_email(): void
    {
        $validator = new EmailAddressValidator();

        $valid = $validator->isValid('a@b.com');

        $this->assertEquals(true, $valid);
    }

    #[Test]
    public function correctly_validates_a_known_bad_email_as_bad(): void
    {
        $validator = new EmailAddressValidator();

        $valid = $validator->isValid('notan@email');

        $this->assertEquals(false, $valid);
    }

    #[Test]
    public function correctly_sets_our_message_when_bad_email_validated(): void
    {
        $validator = new EmailAddressValidator();

        $valid = $validator->isValid('notan@email');

        $this->assertEquals(false, $valid);
        $this->assertEquals('Enter an email address in the correct format, like name@example.com', $validator->getMessages()[0]);
    }
}
