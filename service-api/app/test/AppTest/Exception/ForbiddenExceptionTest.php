<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\ForbiddenException;
use PHPUnit\Framework\TestCase;

class ForbiddenExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'fe message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $fe = new ForbiddenException($message, $additionalData);

        $this->assertEquals('Forbidden', $fe->getTitle());
        $this->assertEquals($additionalData, $fe->getAdditionalData());

        $this->assertEquals($message, $fe->getMessage());
        $this->assertEquals(403, $fe->getCode());
    }
}
