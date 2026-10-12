<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Logging;

use Common\Middleware\Logging\RequestTracingMiddleware;
use Common\Service\Container\ModifiableContainerInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(RequestTracingMiddleware::class)]
final class RequestTracingMiddlewareTest extends TestCase
{
    #[Test]
    #[DataProvider('tracingHeaders')]
    public function it_should_store_the_trace_id_and_pass_it_to_the_handler(array $headers, string $traceId): void
    {
        $container = $this->createMock(ModifiableContainerInterface::class);
        $container->expects(self::once())->method('setValue')->with('trace_id', $traceId);

        $request  = new ServerRequest(headers: $headers);
        $response = new Response();
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')
            ->with(self::callback(
                static fn (ServerRequestInterface $request): bool => $request->getAttribute('trace_id') === $traceId,
            ))
            ->willReturn($response);

        self::assertSame($response, (new RequestTracingMiddleware($container))->process($request, $handler));
    }

    public static function tracingHeaders(): array
    {
        return [
            'tracing header'    => [
                ['x-amzn-trace-id' => ['Root=1-1-11']],
                'Root=1-1-11',
            ],
            'no tracing header' => [
                [],
                '',
            ],
            'multiple values'   => [
                [
                    'x-amzn-trace-id' => [
                        'Root=1-1-11',
                        'Root=1-1-22',
                    ],
                ],
                'Root=1-1-11',
            ],
        ];
    }
}
