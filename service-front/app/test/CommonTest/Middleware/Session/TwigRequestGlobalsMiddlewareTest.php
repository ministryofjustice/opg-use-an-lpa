<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Session;

use Common\Middleware\Session\SessionExpiryMiddleware;
use Common\Middleware\Session\TwigRequestGlobalsMiddleware;
use Common\Service\SystemMessage\SystemMessageService;
use Common\View\Twig\RequestGlobalsExtension;
use Common\View\Twig\RequestGlobalsRuntime;
use DI\Container;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

#[CoversClass(TwigRequestGlobalsMiddleware::class)]
final class TwigRequestGlobalsMiddlewareTest extends TestCase
{
    private RequestGlobalsRuntime $runtime;
    private Environment $twig;
    private TwigRequestGlobalsMiddleware $middleware;
    private SystemMessageService&MockObject $messageService;

    protected function setUp(): void
    {
        $this->messageService = $this->createMock(SystemMessageService::class);
        $this->runtime        = new RequestGlobalsRuntime($this->messageService);
        $this->twig           = new Environment(new ArrayLoader([
            'header' => '{% if user() %}{{ user().identity }}{% else %}anonymous{% endif %}',
        ]), ['strict_variables' => true]);
        $loader               = new ContainerRuntimeLoader(new Container([
            RequestGlobalsRuntime::class => $this->runtime,
        ]));
        $this->twig->addRuntimeLoader($loader);
        $this->twig->addExtension(new RequestGlobalsExtension());
        $this->middleware = new TwigRequestGlobalsMiddleware($this->runtime);
    }

    #[Test]
    public function it_should_isolate_globals_between_requests_and_clear_them_afterwards(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        self::assertSame('anonymous', $this->twig->render('header'));

        foreach (['first-user', 'second-user', null] as $identity) {
            $session = new Session($identity === null ? [] : [
                UserInterface::class => [
                    'username' => $identity,
                    'details'  => ['Email' => 'user@example.com'],
                ],
            ]);
            $request = (new ServerRequest())
                ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
                ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, FlashMessages::createFromSession($session));
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects(self::once())->method('handle')->with($request)->willReturnCallback(
                fn (ServerRequestInterface $request) => new HtmlResponse($this->twig->render('header')),
            );

            $response = $this->middleware->process($request, $handler);

            self::assertSame($identity ?? 'anonymous', (string) $response->getBody());
            self::assertNull($this->runtime->getUser());
            self::assertSame('anonymous', $this->twig->render('header'));
        }
    }

    #[Test]
    public function it_should_clear_the_user_and_globals_when_the_handler_throws(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $request = (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, new Session([
            UserInterface::class => [
                'username' => 'account-id',
                'details'  => ['Email' => 'user@example.com'],
            ],
        ]))->withAttribute(
            FlashMessageMiddleware::FLASH_ATTRIBUTE,
            $this->createStub(FlashMessagesInterface::class),
        );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(function (): never {
            self::assertSame('account-id', $this->twig->render('header'));
            self::assertInstanceOf(FlashMessagesInterface::class, $this->runtime->getFlashMessages());
            throw new RuntimeException('Handler failed');
        });

        try {
            $this->middleware->process($request, $handler);
            self::fail('Expected the handler exception to propagate');
        } catch (RuntimeException $exception) {
            self::assertSame('Handler failed', $exception->getMessage());
        }

        self::assertNull($this->runtime->getUser());
        self::assertNull($this->runtime->getFlashMessages());
        self::assertSame('anonymous', $this->twig->render('header'));
    }

    #[Test]
    public function it_should_require_session_middleware_to_run_first(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->expectException(MissingSessionContainerException::class);

        $this->middleware->process(new ServerRequest(), $handler);
    }

    #[Test]
    public function it_should_clear_cached_messages_even_when_a_request_fails(): void
    {
        $this->messageService->expects(self::exactly(2))->method('getMessages')->willReturnOnConsecutiveCalls(
            ['use/en' => 'First request'],
            ['use/en' => 'Second request'],
        );
        $loader = $this->twig->getLoader();
        self::assertInstanceOf(ArrayLoader::class, $loader);
        $loader->setTemplate('message', '{{ system_messages()["use/en"] }}');
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, new Session([]))
            ->withAttribute(
                FlashMessageMiddleware::FLASH_ATTRIBUTE,
                $this->createStub(FlashMessagesInterface::class),
            );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(function (): never {
            self::assertSame('First request', $this->twig->render('message'));
            self::assertSame('First request', $this->twig->render('message'));
            throw new RuntimeException('Handler failed');
        });

        try {
            $this->middleware->process($request, $handler);
            self::fail('Expected the handler exception to propagate');
        } catch (RuntimeException $exception) {
            self::assertSame('Handler failed', $exception->getMessage());
        }

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            fn () => new HtmlResponse($this->twig->render('message')),
        );
        $response = $this->middleware->process($request, $handler);

        self::assertSame('Second request', (string) $response->getBody());
    }

    #[Test]
    public function it_should_require_flash_message_middleware_to_run_first(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $request = (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, new Session([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Flash message middleware must run before Twig request globals middleware',
        );

        $this->middleware->process($request, $handler);
    }

    #[Test]
    public function it_should_render_flash_messages_for_one_request_without_leaking_or_advancing_them(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $loader = $this->twig->getLoader();
        self::assertInstanceOf(ArrayLoader::class, $loader);
        $loader->setTemplate('flash', '{{ flash().getFlash("added") }}');
        $session = new Session([]);
        $flash   = FlashMessages::createFromSession($session);
        $flash->flash('added', '<LPA added>');

        foreach (['&lt;LPA added&gt;', ''] as $expected) {
            $flash   = FlashMessages::createFromSession($session);
            $request = (new ServerRequest())
                ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
                ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash);
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects(self::once())->method('handle')->willReturnCallback(
                function () use ($flash, $expected): HtmlResponse {
                    self::assertSame($flash, $this->runtime->getFlashMessages());
                    self::assertSame($expected, $this->twig->render('flash'));

                    return new HtmlResponse($this->twig->render('flash'));
                },
            );

            $response = $this->middleware->process($request, $handler);

            self::assertSame($expected, (string) $response->getBody());
            self::assertNull($this->runtime->getFlashMessages());
        }
    }

    #[Test]
    public function it_should_reconstruct_the_user_before_the_handler_and_not_retain_the_session(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $session = new Session([
            UserInterface::class => [
                'username' => 'account-id',
                'details'  => [
                    'Email'     => 'user@example.com',
                    'LastLogin' => '2026-10-01T10:00:00+00:00',
                ],
            ],
        ]);
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, FlashMessages::createFromSession($session));
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(function () use ($session): HtmlResponse {
            $user = $this->runtime->getUser();
            self::assertInstanceOf(UserInterface::class, $user);
            self::assertSame('account-id', $user->getIdentity());
            self::assertSame('user@example.com', $user->getDetail('Email'));
            self::assertSame([], $user->getRoles());
            self::assertSame('2026-10-01', $user->getDetail('LastLogin')->format('Y-m-d'));
            $session->unset(UserInterface::class);
            self::assertSame($user, $this->runtime->getUser());

            return new HtmlResponse($this->twig->render('header'));
        });

        self::assertSame('account-id', (string) $this->middleware->process($request, $handler)->getBody());
        self::assertNull($this->runtime->getUser());
    }

    #[Test]
    #[DataProvider('unauthenticatedSessions')]
    public function it_should_bind_no_user_without_a_valid_login(Session $session): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, FlashMessages::createFromSession($session));
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(function (): HtmlResponse {
            self::assertNull($this->runtime->getUser());

            return new HtmlResponse($this->twig->render('header'));
        });

        self::assertSame('anonymous', (string) $this->middleware->process($request, $handler)->getBody());
    }

    public static function unauthenticatedSessions(): array
    {
        return [
            'anonymous'    => [new Session([])],
            'invalid user' => [new Session([UserInterface::class => 'invalid'])],
            'missing id'   => [new Session([UserInterface::class => []])],
            'expired'      => [
                new Session([
                    SessionExpiryMiddleware::SESSION_EXPIRED_KEY => true,
                    UserInterface::class                         => [
                        'username' => 'account-id',
                        'details'  => ['Email' => 'user@example.com'],
                    ],
                ]),
            ],
        ];
    }

    #[Test]
    public function it_should_propagate_invalid_user_details_before_calling_the_handler(): void
    {
        $this->messageService->expects(self::never())->method('getMessages');
        $session = new Session([UserInterface::class => ['username' => 'account-id']]);
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, FlashMessages::createFromSession($session));
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected database value "Email" not returned');

        $this->middleware->process($request, $handler);
    }
}
