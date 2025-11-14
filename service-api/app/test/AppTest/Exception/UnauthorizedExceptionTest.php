<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class UnauthorizedExceptionTest extends TestCase
{
    public function testDataGets(): void
    {
        $message = 'ue message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $ue = new UnauthorizedException($message, $additionalData);

        $this->assertSame('Unauthorized', $ue->getTitle());
        $this->assertSame($additionalData, $ue->getAdditionalData());

        $this->assertSame($message, $ue->getMessage());
        $this->assertEquals(401, $ue->getCode());
    }
}
