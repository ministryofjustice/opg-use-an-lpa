<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\GoneException;
use PHPUnit\Framework\TestCase;

class GoneExceptionTest extends TestCase
{
    public function testDataGets(): void
    {
        $message = 'ge message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $ge = new GoneException($message, $additionalData);

        $this->assertSame('Gone', $ge->getTitle());
        $this->assertSame($additionalData, $ge->getAdditionalData());

        $this->assertSame($message, $ge->getMessage());
        $this->assertEquals(410, $ge->getCode());
    }
}
