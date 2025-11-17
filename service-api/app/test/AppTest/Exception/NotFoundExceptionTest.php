<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class NotFoundExceptionTest extends TestCase
{
    public function testDataGets(): void
    {
        $message = 'nfe message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $nfe = new NotFoundException($message, $additionalData);

        $this->assertSame('Not found', $nfe->getTitle());
        $this->assertSame($additionalData, $nfe->getAdditionalData());

        $this->assertSame($message, $nfe->getMessage());
        $this->assertEquals(404, $nfe->getCode());
    }
}
