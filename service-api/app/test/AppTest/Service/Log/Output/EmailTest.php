<?php

declare(strict_types=1);

namespace AppTest\Service\Log\Output;

use App\Service\Log\Output\Email;
use PHPUnit\Framework\Attributes\Test;
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
