<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response\Parse;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Lpa\Response\PaperVerificationCode;
use Common\Service\Lpa\Response\Parse\ParsePaperVerificationCode;
use PHPUnit\Framework\TestCase;

class ParsePaperVerificationCodeTest extends TestCase
{
    #[Test]
    public function parse(): void
    {
        $parser = new ParsePaperVerificationCode();
        $result = $parser(['donorName' => 'Smith', 'type' => 'hw']);

        $this->assertEquals(new PaperVerificationCode('Smith', 'hw'), $result);
    }
}
