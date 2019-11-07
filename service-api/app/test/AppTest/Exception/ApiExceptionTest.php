<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\AbstractApiException;
use App\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ApiExceptionTest extends TestCase
{
    /** @test */
    public function creates_instance_without_response()
    {
        $instance = ApiException::create('test');

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('test', $instance->getMessage());
        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
    }

    /** @test */
    public function creates_instance_with_response()
    {
        $message = 'api message';
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()
            ->willReturn(json_encode($additionalData));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());
        $responseProphecy->getStatusCode()
            ->willReturn(404);

        $ex = ApiException::create($message, $responseProphecy->reveal());

        $this->assertInstanceOf(ApiException::class, $ex);
        $this->assertInstanceOf(AbstractApiException::class, $ex);

        $this->assertEquals(ApiException::DEFAULT_TITLE, $ex->getTitle());
        $this->assertEquals($additionalData, $ex->getAdditionalData());

        $this->assertEquals($message, $ex->getMessage());
        $this->assertEquals(404, $ex->getCode());
    }
}