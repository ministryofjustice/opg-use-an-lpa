<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use PHPUnit\Framework\Attributes\Test;
use Common\Middleware\Authentication\CredentialAuthenticationMiddleware;
use Common\Middleware\Authentication\ForcedPasswordResetMiddleware;
use Common\Middleware\Authentication\AuthenticationMiddlewareFactory;
use Laminas\Stratigility\MiddlewarePipeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    #[Test]
    public function it_pipes_a_request_through_all_authentication_middlewares(): void
    {
        $pipe                               = $this->createMock(MiddlewarePipeInterface::class);
        $credentialAuthenticationMiddleware = $this->createMock(CredentialAuthenticationMiddleware::class);
        $forcedPasswordResetMiddleware      = $this->createMock(ForcedPasswordResetMiddleware::class);

        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap(
            [
                 [MiddlewarePipeInterface::class, $pipe],
                 [CredentialAuthenticationMiddleware::class, $credentialAuthenticationMiddleware],
                 [ForcedPasswordResetMiddleware::class, $forcedPasswordResetMiddleware],
                 [
            'config',
            [
                     'feature_flags' => [
                         'allow_gov_one_login' => false,
                     ],
                 ],
                 ],
             ]
        );

        $pipe->expects($this->once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);
        $matcher = $this->exactly(1);

        $pipe->expects($matcher)
            ->method('pipe')->willReturnCallback(function ($parameter) use ($matcher, $credentialAuthenticationMiddleware, $forcedPasswordResetMiddleware) {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertEquals($credentialAuthenticationMiddleware, $parameter),
                    2 => self::assertEquals($forcedPasswordResetMiddleware, $parameter),
                };
            });

        $factory = new AuthenticationMiddlewareFactory();
        $sut     = $factory($container);

        $result = $sut->process($request, $handler);
        $this->assertSame($response, $result);
    }

    #[Test]
    public function it_excludes_forced_password_reset_when_gov_one_login_enabled(): void
    {
        $pipe                               = $this->createMock(MiddlewarePipeInterface::class);
        $credentialAuthenticationMiddleware = $this->createMock(CredentialAuthenticationMiddleware::class);
        $forcedPasswordResetMiddleware      = $this->createMock(ForcedPasswordResetMiddleware::class);

        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap([
                                                     [MiddlewarePipeInterface::class, $pipe],
                                                     [CredentialAuthenticationMiddleware::class, $credentialAuthenticationMiddleware],
                                                     [ForcedPasswordResetMiddleware::class, $forcedPasswordResetMiddleware],
                                                     [
        'config',
        [
                                                         'feature_flags' => [],
                                                     ],
                                                     ],
                                                 ]);

        $pipe->expects($this->once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $pipe->expects($this->exactly(1))
            ->method('pipe')
            ->with($credentialAuthenticationMiddleware);

        $factory = new AuthenticationMiddlewareFactory();
        $sut     = $factory($container);

        $result = $sut->process($request, $handler);
        $this->assertSame($response, $result);
    }
}
