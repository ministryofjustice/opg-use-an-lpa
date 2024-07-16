<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Authentication;

use Common\Middleware\Authentication\AuthenticationMiddleware;
use Common\Middleware\Authentication\CredentialAuthenticationMiddleware;
use Common\Middleware\Authentication\ForcedPasswordResetMiddleware;
use Laminas\Stratigility\MiddlewarePipeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    /** @test */
    public function it_pipes_a_request_through_all_authentication_middlewares(): void
    {
        $pipe                               = $this->createMock(MiddlewarePipeInterface::class);
        $credentialAuthenticationMiddleware = $this->createMock(CredentialAuthenticationMiddleware::class);
        $forcedPasswordResetMiddleware      = $this->createMock(ForcedPasswordResetMiddleware::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->createMock(ResponseInterface::class);

        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturn(
            [
                'feature_flags' => [
                    'allow_gov_one_login' => false
                ],
            ]
        );

        $pipe->expects($this->once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $pipe->expects($this->exactly(2))
            ->method('pipe')
            ->withConsecutive(
                [$credentialAuthenticationMiddleware],
                [$forcedPasswordResetMiddleware],
            );

        $sut = new AuthenticationMiddleware($container, $pipe, $credentialAuthenticationMiddleware, $forcedPasswordResetMiddleware);

        $response = $sut->process($request, $handler);
    }

    /** @test */
    public function it_excludes_forced_password_reset_when_gov_one_login_enabled(): void
    {
        $pipe                               = $this->createMock(MiddlewarePipeInterface::class);
        $credentialAuthenticationMiddleware = $this->createMock(CredentialAuthenticationMiddleware::class);
        $forcedPasswordResetMiddleware      = $this->createMock(ForcedPasswordResetMiddleware::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response  = $this->createMock(ResponseInterface::class);
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturn(
            [
                'feature_flags' => [
                    'allow_gov_one_login' => true
                ],
            ]
        );

        $pipe->expects($this->once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $pipe->expects($this->exactly(1))
            ->method('pipe')
            ->with(
                $credentialAuthenticationMiddleware
              );

        $sut = new AuthenticationMiddleware($container, $pipe, $credentialAuthenticationMiddleware, $forcedPasswordResetMiddleware);

        $result = $sut->process($request, $handler);
        $this->assertSame($response, $result);
    }
}
