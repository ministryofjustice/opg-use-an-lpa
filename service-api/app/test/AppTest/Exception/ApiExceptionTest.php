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
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($additionalData));

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

    /** @test */
    public function can_get_exception_message_from_body_details()
    {
        $message = null;
        $body = [
            'details' => 'test exception message',
        ];

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($body));

        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy->getStatusCode()
            ->willReturn(500);

        $instance = ApiException::create($message, $responseProphecy->reveal());

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('test exception message', $instance->getMessage());
        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
    }

    /** @test */
    public function can_compose_a_message_from_body_if_no_details()
    {
        $message = null;
        $body = ['some' => 'other data'];

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($body));

        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy->getStatusCode()
            ->willReturn(500);

        $instance = ApiException::create($message, $responseProphecy->reveal());

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('HTTP: 500 - ' . print_r($body, true), $instance->getMessage());

        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
    }

    /** @test */
    public function can_compose_a_standard_message_if_none_found()
    {
        $message = null;

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()
            ->willReturn(json_encode('body test data'));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());
        $responseProphecy->getStatusCode()
            ->willReturn(500);

        $instance = ApiException::create($message, $responseProphecy->reveal());

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('HTTP: 500 - Unexpected API response', $instance->getMessage());
        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
    }

    /** @test */
    public function can_function_when_lacking_an_associated_response_object()
    {
        $instance = ApiException::create('This is an exception', null);

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('This is an exception', $instance->getMessage());
        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
        $this->assertIsArray($instance->getAdditionalData());
    }
}
