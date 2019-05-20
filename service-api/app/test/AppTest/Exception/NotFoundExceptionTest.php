<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use App\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class NotFoundExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'nfe message';
        $title = 'nfe title';
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $nfe = new NotFoundException($message, $title, $additionalData);

        $this->assertEquals($title, $nfe->getTitle());
        $this->assertEquals($additionalData, $nfe->getAdditionalData());

        $this->assertEquals($message, $nfe->getMessage());
        $this->assertEquals(404, $nfe->getCode());
    }
}
