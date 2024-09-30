<?php

declare(strict_types=1);

namespace CommonTest\Service\Log\Output;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Log\Output\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    #[Test]
    public function it_hides_a_string(): void
    {
        $email = new Email('test@test.com');
        $this->assertMatchesRegularExpression('/.*/', (string)$email);
        $this->assertStringNotContainsString('test@test.com', (string)$email);
    }
}
