<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\GoneException;
use PHPUnit\Framework\TestCase;

class GoneExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'ge message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $ge = new GoneException($message, $additionalData);

        $this->assertEquals('Gone', $ge->getTitle());
        $this->assertEquals($additionalData, $ge->getAdditionalData());

        $this->assertEquals($message, $ge->getMessage());
        $this->assertEquals(410, $ge->getCode());
    }
}
