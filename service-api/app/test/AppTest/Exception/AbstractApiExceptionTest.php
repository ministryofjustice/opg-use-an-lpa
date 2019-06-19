<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\AbstractApiException;
use AppTest\Exception\Mocks\BadApiException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractApiExceptionTest extends TestCase
{
    public function testCannotCreateBadExceptionCode()
    {
        $this->expectException(RuntimeException::class);

        $exception = new BadApiException('test');
    }

    public function testHasNoMessageEqualsTitle()
    {
        $noMessage = [
            'title'
        ];

        $exception = $this->getMockForAbstractClass(
            AbstractApiException::class,
            $noMessage
        );

        $this->assertEquals('title', $exception->getMessage());
    }
}