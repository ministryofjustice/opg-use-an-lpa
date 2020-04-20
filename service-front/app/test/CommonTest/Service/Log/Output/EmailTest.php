<?php

declare(strict_types=1);

namespace CommonTest\Service\Log\Output;

use Common\Service\Log\Output\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    /** @test */
    public function it_hides_a_string()
    {
        $email = new Email('test@test.com');
        $this->assertRegExp('/.*/', (string)$email);
        $this->assertNotContains('test@test.com', (string)$email);
    }
}
