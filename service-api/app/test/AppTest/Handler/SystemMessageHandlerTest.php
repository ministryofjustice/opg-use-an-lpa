<?php


declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SystemMessageHandler;
use App\Service\SystemMessage;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class SystemMessageHandlerTest extends TestCase
{
    private SystemMessage|\PHPUnit\Framework\MockObject\MockObject $systemMessageService;
    private SystemMessageHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->systemMessageService = $this->createMock(SystemMessage::class);

        $this->handler = new SystemMessageHandler($this->systemMessageService);
    }

    /**
     * @throws Exception
     * @throws IncompatibleReturnValueException
     * @throws ExpectationFailedException
     */
    public function testHandleReturnsJsonResponseWithSystemMessages(): void
    {
        $expectedMessages = [
            '/system-message/use/en' => 'English usage message',
            '/system-message/use/cy' => 'Welsh usage message',
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
