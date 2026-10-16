<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Exception\UnauthorizedException;
use App\Middleware\UserIdentificationMiddleware;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;

#[CoversClass(UserIdentificationMiddleware::class)]
final class UserIdentificationMiddlewareTest extends TestCase
{
    #[Test]
    public function it_should_pass_actor_attributes_to_the_handler_and_return_its_response(): void
    {
        $request  = (new ServerRequest())
            ->withHeader('User-Token', 'test-token-123')
            ->withHeader('User-Session', 'test-session-123');
        $delegate = $this->createMock(DelegateInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $delegate->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $request): bool {
                $this->assertSame('test-token-123', $request->getAttribute('actor-id'));
                $this->assertSame('test-session-123', $request->getAttribute('actor-session'));

                return true;
            }))
            ->willReturn($response);

        $middleware = new UserIdentificationMiddleware();

        $this->assertSame($response, $middleware->process($request, $delegate));
    }

    #[Test]
    public function it_should_throw_an_unauthorized_exception_when_no_user_token(): void
    {
        $request  = (new ServerRequest())->withHeader('User-Session', 'test-session-123');
        $delegate = $this->createMock(DelegateInterface::class);
        $delegate->expects($this->never())->method('handle');

        $middleware = new UserIdentificationMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('User-Token not specified or invalid');

        $middleware->process($request, $delegate);
    }

    #[Test]
    public function it_should_throw_an_unauthorized_exception_when_no_user_session(): void
    {
        $request  = (new ServerRequest())->withHeader('User-Token', 'test-token-123');
        $delegate = $this->createMock(DelegateInterface::class);
        $delegate->expects($this->never())->method('handle');

        $middleware = new UserIdentificationMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('User-Token not specified or invalid');

        $middleware->process($request, $delegate);
    }
}
