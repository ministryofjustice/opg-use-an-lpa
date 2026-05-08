<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\UnauthorizedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UnauthorizedExceptionTest extends TestCase
{
    #[Test]
    public function dataGets(): void
    {
        $message = 'ue message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $ue = new UnauthorizedException($message, $additionalData);

        $this->assertEquals('Unauthorized', $ue->getTitle());
        $this->assertEquals($additionalData, $ue->getAdditionalData());

        $this->assertEquals($message, $ue->getMessage());
        $this->assertEquals(401, $ue->getCode());
    }
}
