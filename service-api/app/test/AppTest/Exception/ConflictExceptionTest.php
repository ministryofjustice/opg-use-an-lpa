<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\ConflictException;
use PHPUnit\Framework\TestCase;

class ConflictExceptionTest extends TestCase
{
    public function testDataGets(): void
    {
        $message = 'ce message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $ce = new ConflictException($message, $additionalData);

        $this->assertSame('Conflict', $ce->getTitle());
        $this->assertSame($additionalData, $ce->getAdditionalData());

        $this->assertSame($message, $ce->getMessage());
        $this->assertEquals(409, $ce->getCode());
    }
}
