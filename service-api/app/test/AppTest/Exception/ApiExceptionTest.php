<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ApiExceptionTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function creates_instance_without_response(): void
    {
        $instance = ApiException::create('test');

        $this->assertSame('test', $instance->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $instance->getCode());
    }

    #[Test]
    public function creates_instance_with_response(): void
    {
        $message        = 'api message';
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $streamProphecy   = $this->prophesize(StreamInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($additionalData));

        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy->getStatusCode()
            ->willReturn(404);

        $ex = ApiException::create($message, $responseProphecy->reveal());

        $this->assertSame(ApiException::DEFAULT_TITLE, $ex->getTitle());
        $this->assertSame($additionalData, $ex->getAdditionalData());
        $this->assertSame($additionalData, $ex->getAdditionalDataForLogging());

        $this->assertSame($message, $ex->getMessage());
        $this->assertEquals(404, $ex->getCode());
    }

    #[Test]
    public function can_get_exception_message_from_body_details(): void
    {
        $message = null;
        $body    = [
            'details' => 'test exception message',
        ];

        $streamProphecy   = $this->prophesize(StreamInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($body));

        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy->getStatusCode()
            ->willReturn(500);

        $instance = ApiException::create($message, $responseProphecy->reveal());

        $this->assertSame('test exception message', $instance->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $instance->getCode());
    }

    #[Test]
    public function can_compose_a_message_from_body_if_no_details(): void
    {
        $message = null;
        $body    = ['some' => 'other data'];

        $streamProphecy   = $this->prophesize(StreamInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $streamProphecy->getContents()
            ->willReturn(json_encode($body));

        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy->getStatusCode()
            ->willReturn(500);

        $instance = ApiException::create($message, $responseProphecy->reveal());

        $this->assertSame('HTTP: 500 - ' . print_r($body, true), $instance->getMessage());

        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $instance->getCode());
    }

    #[Test]
    public function can_compose_a_standard_message_if_none_found(): void
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

        $this->assertSame('HTTP: 500 - Unexpected API response', $instance->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $instance->getCode());
    }

    #[Test]
    public function can_function_when_lacking_an_associated_response_object(): void
    {
        $instance = ApiException::create('This is an exception', null);

        $this->assertSame('This is an exception', $instance->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $instance->getCode());
        $this->assertIsArray($instance->getAdditionalData());
        $this->assertIsArray($instance->getAdditionalDataForLogging());
    }
}
