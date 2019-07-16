<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\BadRequestException;
use PHPUnit\Framework\TestCase;

class BadRequestExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'bre message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $bre = new BadRequestException($message, $additionalData);

        $this->assertEquals('Bad Request', $bre->getTitle());
        $this->assertEquals($additionalData, $bre->getAdditionalData());

        $this->assertEquals($message, $bre->getMessage());
        $this->assertEquals(400, $bre->getCode());
    }
}
