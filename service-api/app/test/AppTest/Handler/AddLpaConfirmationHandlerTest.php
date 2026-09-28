<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Handler\AddLpaConfirmationHandler;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\ValidatedActorCode;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use App\Service\Lpa\ResolveActor\ActorType;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class AddLpaConfirmationHandlerTest extends TestCase
{
    private ActorCodeService|MockObject $actorCodeService;
    private AddLpaConfirmationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actorCodeService = $this->createMock(ActorCodeService::class);

        $this->handler = new AddLpaConfirmationHandler($this->actorCodeService);
    }

    #[Test]
    public function handleThrowsNotFoundExceptionWhenValidationFails(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'actor-code' => 'test-code',
                'uid'        => 'test-uid',
                'dob'        => '1982-10-28',
            ]);

        $this->actorCodeService->method('validateDetails')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->handler->handle($request);
    }

    #[Test]
    public function handleThrowsBadRequestExceptionWhenMissingActorCode(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'uid' => 'test-uid',
                'dob' => '1982-10-28',
            ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("'actor-code', 'uid' and 'dob' are required fields");

        $this->handler->handle($request);
    }

    #[Test]
    public function handleThrowsBadRequestExceptionWhenMissingUid(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'actor-code' => 'test-code',
                'dob'        => '1982-10-28',
            ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("'actor-code', 'uid' and 'dob' are required fields");

        $this->handler->handle($request);
    }

    #[Test]
    public function handleThrowsBadRequestExceptionWhenMissingDob(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'actor-code' => 'test-code',
                'uid'        => 'test-uid',
            ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("'actor-code', 'uid' and 'dob' are required fields");

        $this->handler->handle($request);
    }

    #[Test]
    public function handleThrowsNotFoundExceptionWhenConfirmationFails(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        $lpa = new SiriusLpa(
            ['uId' => 'test-uid'],
            $loggerMock,
        );

        $actor = new LpaActor(
            new SiriusPerson(
                [
                    'dob' => '1982-10-28',
                    'id'  => 1,
                    'uId' => 'test-actor-uid',
                ],
                $loggerMock,
            ),
            ActorType::ATTORNEY,
        );

        $validatedDetails = new ValidatedActorCode($actor, $lpa, false);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'actor-code' => 'test-code',
                'uid'        => 'test-uid',
                'dob'        => '1982-10-28',
            ]);

        $this->actorCodeService->method('validateDetails')
            ->willReturn($validatedDetails);

        $this->actorCodeService->method('confirmDetails')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->handler->handle($request);
    }

    #[Test]
    public function handleReturnsSuccessResponseWhenConfirmationSucceeds(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        $lpa = new SiriusLpa(
            ['uId' => 'test-uid'],
            $loggerMock,
        );

        $actor = new LpaActor(
            new SiriusPerson(
                [
                    'dob' => '1982-10-28',
                    'id'  => 1,
                    'uId' => 'test-actor-uid',
                ],
                $loggerMock,
            ),
            ActorType::ATTORNEY,
        );

        $validatedDetails = new ValidatedActorCode($actor, $lpa, false);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('user-token', 'test-user')
            ->withParsedBody([
                'actor-code' => 'test-code',
                'uid'        => 'test-uid',
                'dob'        => '1982-10-28',
            ]);

        $this->actorCodeService->method('validateDetails')
            ->willReturn($validatedDetails);

        $expectedToken = '00000000-0000-4000-A000-000000000000';
        $this->actorCodeService->method('confirmDetails')
            ->willReturn($expectedToken);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['user-lpa-actor-token' => $expectedToken], $responseBody);
    }
}
