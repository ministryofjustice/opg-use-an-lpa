<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\Code;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{
    #[Test]
    public function can_be_a_paper_verification_code(): void
    {
        $code = new Code('P-1234-1234-1234-12');

        $this->assertEquals('P-1234-1234-1234-12', $code->value);
        $this->assertTrue($code->isPaperVerificationCode());
    }

    #[Test]
    public function can_be_some_other_prefix_code(): void
    {
        $code = new Code('W-1234-1234-1234-12');

        $this->assertEquals('W-1234-1234-1234-12', $code->value);
        $this->assertFalse($code->isPaperVerificationCode());
    }

    #[Test]
    public function can_be_some_other_length_code(): void
    {
        $code = new Code('123412341234');

        $this->assertEquals('123412341234', $code->value);
        $this->assertFalse($code->isPaperVerificationCode());
    }
}
