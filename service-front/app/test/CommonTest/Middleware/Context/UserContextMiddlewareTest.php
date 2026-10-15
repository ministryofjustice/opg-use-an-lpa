<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Context;

use Common\Middleware\Context\UserContextMiddleware;
use Common\Service\Context\UserContext;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

#[CoversClass(UserContextMiddleware::class)]
#[UsesClass(UserContext::class)]
final class UserContextMiddlewareTest extends TestCase
{
    private function request(string $sessionId): ServerRequestInterface
    {
        $session = new Session([], $sessionId);

        return (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
    }

    #[Test]
    public function it_should_clear_context_on_exception_and_allow_the_next_request(): void
    {
        $context    = new UserContext();
        $middleware = new UserContextMiddleware($context);
        $failure    = new RuntimeException('Downstream failure');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            function (ServerRequestInterface $request) use ($failure): never {
                $request->getAttribute(UserContext::class)->userId = 'authenticated-later';
                throw $failure;
            }
        );

        try {
            $middleware->process($this->request('first-session'), $handler);
            self::fail('The downstream exception must propagate');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertNull($context->userId);
        self::assertNull($context->sessionId);

        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects(self::once())->method('handle')->willReturn(new Response());
        $middleware->process($this->request('next-session'), $next);

        self::assertNull($context->sessionId);
    }

    #[Test]
    public function it_should_clear_stale_context_when_the_session_is_missing(): void
    {
        $context    = new UserContext('stale-user', 'stale-session');
        $middleware = new UserContextMiddleware($context);
        $handler    = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        try {
            $middleware->process(new ServerRequest(), $handler);
            self::fail('A missing session must be rejected');
        } catch (MissingSessionContainerException) {
            self::assertNull($context->userId);
            self::assertNull($context->sessionId);
        }
    }
}
