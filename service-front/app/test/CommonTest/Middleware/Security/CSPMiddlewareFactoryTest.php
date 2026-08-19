<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\CSPMiddleware;
use Common\Middleware\Security\CSPMiddlewareFactory;
use Common\Service\Security\CSPNonce;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use RuntimeException;

#[CoversClass(CSPMiddlewareFactory::class)]
class CSPMiddlewareFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function validConfig(): array
    {
        return [
            'application' => 'actor',
            'csp'         => [
                'enforce'               => true,
                'report_uri'            => 'https://report_uri',
                'authentication_domain' => 'https://*.authentication_domain',
                'iap_domain'            => 'https://images_domain',
            ],
        ];
    }

    #[Test]
    public function it_creates_a_csp_middleware_when_configuration_is_valid(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('config')
            ->willReturn(true);
        $containerProphecy->get('config')
            ->willReturn($this->validConfig());
        $containerProphecy->get(CSPNonce::class)
            ->willReturn(new CSPNonce('test'));

        $factory  = new CSPMiddlewareFactory();
        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(CSPMiddleware::class, $instance);
    }

    #[Test]
    public function it_throws_an_exception_when_config_is_missing_from_container(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('config')
            ->willReturn(false);

        $factory = new CSPMiddlewareFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSP configuration value missing');

        $factory($containerProphecy->reveal());
    }

    #[Test]
    public function it_throws_an_exception_when_csp_config_is_missing_entirely(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('config')
            ->willReturn(true);
        $containerProphecy->get('config')
            ->willReturn(['application' => 'actor']);

        $factory = new CSPMiddlewareFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSP configuration value missing');

        $factory($containerProphecy->reveal());
    }

    #[Test]
    public function it_throws_an_exception_when_a_csp_config_key_is_missing(): void
    {
        $config = $this->validConfig();
        unset($config['csp']['iap_domain']);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('config')
            ->willReturn(true);
        $containerProphecy->get('config')
            ->willReturn($config);

        $factory = new CSPMiddlewareFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSP configuration value missing');

        $factory($containerProphecy->reveal());
    }
}
