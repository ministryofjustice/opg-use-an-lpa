<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Twig;

use Common\Middleware\Twig\TwigRequestGlobalsMiddleware;
use Common\Service\SystemMessage\SystemMessageService;
use Common\View\Twig\RequestGlobalsRuntime;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

#[CoversClass(TwigRequestGlobalsMiddleware::class)]
final class TwigRequestGlobalsMiddlewareTest extends TestCase
{
    private RequestGlobalsRuntime $runtime;
    private TwigRequestGlobalsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->runtime    = new RequestGlobalsRuntime($this->createStub(SystemMessageService::class));
        $this->middleware = new TwigRequestGlobalsMiddleware($this->runtime);
    }

    #[Test]
    public function it_should_bind_request_state_before_dispatch_and_reset_it_afterwards(): void
    {
        $this->runtime->setUser($this->createStub(UserInterface::class));
        $this->runtime->setFlashMessages($this->createStub(FlashMessagesInterface::class));

        $session = $this->createStub(SessionInterface::class);
        $session->method('has')->willReturn(false);
        $session->method('get')->willReturn([
            'username' => 'account-id',
            'roles'    => [],
            'details'  => ['Email' => 'user@example.com'],
        ]);

        $flash = $this->createStub(FlashMessagesInterface::class);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash);

        $response = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturnCallback(
            function () use ($flash, $response): Response {
                $user = $this->runtime->getUser();
                self::assertInstanceOf(UserInterface::class, $user);
                self::assertSame('account-id', $user->getIdentity());
                self::assertSame('user@example.com', $user->getDetail('Email'));
                self::assertSame($flash, $this->runtime->getFlashMessages());

                return $response;
            },
        );

        self::assertSame($response, $this->middleware->process($request, $handler));
        self::assertNull($this->runtime->getUser());
        self::assertNull($this->runtime->getFlashMessages());
    }

    #[Test]
    #[DataProvider('unauthenticatedSessions')]
    public function it_should_bind_no_user_without_a_valid_login(bool $expired, mixed $userInfo): void
    {
        $this->runtime->setUser($this->createStub(UserInterface::class));

        $session = $this->createStub(SessionInterface::class);
        $session->method('has')->willReturn($expired);
        $session->method('get')->willReturn($userInfo);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(
                FlashMessageMiddleware::FLASH_ATTRIBUTE,
                $this->createStub(FlashMessagesInterface::class),
            );

        $response = new Response();
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturnCallback(
            function () use ($response): Response {
                self::assertNull($this->runtime->getUser());

                return $response;
            },
        );

        self::assertSame($response, $this->middleware->process($request, $handler));
    }

    public static function unauthenticatedSessions(): array
    {
        return [
            'anonymous'    => [
                false,
                null,
            ],
            'invalid user' => [
                false,
                'invalid',
            ],
            'missing id'   => [
                false,
                [],
            ],
            'expired'      => [
                true,
                [
                    'username' => 'account-id',
                    'details'  => ['Email' => 'user@example.com'],
                ],
            ],
        ];
    }

    #[Test]
    public function it_should_reset_request_state_and_propagate_the_handler_exception(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('has')->willReturn(false);
        $session->method('get')->willReturn([
            'username' => 'account-id',
            'details'  => ['Email' => 'user@example.com'],
        ]);

        $flash = $this->createStub(FlashMessagesInterface::class);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash);

        $exception = new RuntimeException('Handler failed');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturnCallback(
            function () use ($flash, $exception): never {
                self::assertInstanceOf(UserInterface::class, $this->runtime->getUser());
                self::assertSame($flash, $this->runtime->getFlashMessages());
                throw $exception;
            },
        );

        try {
            $this->middleware->process($request, $handler);
            self::fail('Expected the handler exception to propagate');
        } catch (RuntimeException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertNull($this->runtime->getUser());
        self::assertNull($this->runtime->getFlashMessages());
    }

    #[Test]
    public function it_should_reject_a_missing_session_before_dispatch(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->expectException(MissingSessionContainerException::class);

        $this->middleware->process(new ServerRequest(), $handler);
    }

    #[Test]
    public function it_should_reject_missing_flash_messages_before_dispatch(): void
    {
        $request = (new ServerRequest())->withAttribute(
            SessionMiddleware::SESSION_ATTRIBUTE,
            $this->createStub(SessionInterface::class),
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Flash message middleware must run before Twig request globals middleware',
        );

        $this->middleware->process($request, $handler);
    }

    #[Test]
    public function it_should_reset_state_if_loading_the_user_fails_before_dispatch(): void
    {
        $this->runtime->setUser($this->createStub(UserInterface::class));
        $this->runtime->setFlashMessages($this->createStub(FlashMessagesInterface::class));

        $session   = $this->createStub(SessionInterface::class);
        $exception = new RuntimeException('Session read failed');
        $session->method('has')->willReturn(false);
        $session->method('get')->willThrowException($exception);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(
                FlashMessageMiddleware::FLASH_ATTRIBUTE,
                $this->createStub(FlashMessagesInterface::class),
            );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        try {
            $this->middleware->process($request, $handler);
            self::fail('Expected session read failure to propagate');
        } catch (RuntimeException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertNull($this->runtime->getUser());
        self::assertNull($this->runtime->getFlashMessages());
    }
}
