<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\ConflictException;
use PHPUnit\Framework\TestCase;

class ConflictExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'ce message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $ce = new ConflictException($message, $additionalData);

        $this->assertEquals('Conflict', $ce->getTitle());
        $this->assertEquals($additionalData, $ce->getAdditionalData());

        $this->assertEquals($message, $ce->getMessage());
        $this->assertEquals(409, $ce->getCode());
    }
}
