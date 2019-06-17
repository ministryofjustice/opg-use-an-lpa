<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\AbstractApiException;
use App\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ApiExceptionTest extends TestCase
{
    public function testCreatesInstanceWithoutResponse()
    {
        $instance = ApiException::create('test');

        $this->assertInstanceOf(ApiException::class, $instance);
        $this->assertInstanceOf(AbstractApiException::class, $instance);

        $this->assertEquals('test', $instance->getMessage());
        $this->assertEquals(ApiException::DEFAULT_ERROR, $instance->getCode());
    }

    public function testCreatesInstanceWithResponse()
    {
        $message = 'api message';
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,'
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn( json_encode($additionalData));
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