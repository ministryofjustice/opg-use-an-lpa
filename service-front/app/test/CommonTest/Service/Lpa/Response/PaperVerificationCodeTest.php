<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Lpa\Response\PaperVerificationCode;
use PHPUnit\Framework\TestCase;

class PaperVerificationCodeTest extends TestCase
{
    #[Test]
    public function it_can_create_a_response(): void
    {
        $dto = new PaperVerificationCode('donor', 'type');

        $this->assertEquals('donor', $dto->donorName);
        $this->assertEquals('type', $dto->lpaType);
    }
}
