<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\PageExpiredException;
use PHPUnit\Framework\TestCase;

class PageExpiredExceptionTest extends TestCase
{
    public function testDataGets()
    {
        $message = 'pee message';
        $title = 'pee title';
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $nfe = new PageExpiredException($message, $title, $additionalData);

        $this->assertEquals($title, $nfe->getTitle());
        $this->assertEquals($additionalData, $nfe->getAdditionalData());

        $this->assertEquals($message, $nfe->getMessage());
        $this->assertEquals(419, $nfe->getCode());
    }
}
