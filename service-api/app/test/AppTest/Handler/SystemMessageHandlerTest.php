<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SystemMessageHandler;
use App\Service\SystemMessage\SystemMessage;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class SystemMessageHandlerTest extends TestCase
{
    private SystemMessage|MockObject $systemMessageService;
    private SystemMessageHandler $handler;

    /**
     * @throws NoPreviousThrowableException
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemMessageService = $this->createMock(SystemMessage::class);

        $this->handler = new SystemMessageHandler($this->systemMessageService);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handleReturnsJsonResponseWithSystemMessages(): void
    {
        $expectedMessages = [
            'use/en' => 'English usage message',
            'use/cy' => 'Welsh usage message',
        ];

        $this->systemMessageService->method('getSystemMessages')
            ->willReturn($expectedMessages);

        $request = $this->createMock(ServerRequestInterface::class);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals($expectedMessages, $responseBody);
    }
}
