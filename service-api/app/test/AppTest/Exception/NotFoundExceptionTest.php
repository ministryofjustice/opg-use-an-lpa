<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class NotFoundExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'nfe message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $nfe = new NotFoundException($message, $additionalData);

        $this->assertEquals('Not found', $nfe->getTitle());
        $this->assertEquals($additionalData, $nfe->getAdditionalData());

        $this->assertEquals($message, $nfe->getMessage());
        $this->assertEquals(404, $nfe->getCode());
    }
}
