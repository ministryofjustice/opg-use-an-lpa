<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\AuthorisationClientManager;
use App\Service\Authentication\AuthorisationClientManagerFactory;
use App\Service\Authentication\IssuerBuilder;
use App\Service\Authentication\JWKFactory;
use App\Service\Authentication\KeyPairManager\OneLoginIdentityKeyPairManager;
use App\Service\Cache\CacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use RuntimeException;

#[CoversClass(AuthorisationClientManagerFactory::class)]
class AuthorisationClientManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_constructs_a_configured_authorisationClientManager(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'one_login' => [
                    'client_id'     => 'fakeClientId',
                    'discovery_url' => 'https://fakeUrl',
                ],
            ]
        );

        $container->get(JWKFactory::class)->willReturn($this->prophesize(JWKFactory::class)->reveal());
        $container
            ->get(OneLoginIdentityKeyPairManager::class)
            ->willReturn(
                $this->prophesize(OneLoginIdentityKeyPairManager::class)->reveal()
            );
        $container->get(IssuerBuilder::class)->willReturn($this->prophesize(IssuerBuilder::class)->reveal());
        $container->get(CacheFactory::class)->willReturn($this->prophesize(CacheFactory::class)->reveal());
        $container
            ->get(PsrClientInterface::class)
            ->willReturn(
                $this->prophesize(PsrClientInterface::class)->reveal()
            );

        $sut = new AuthorisationClientManagerFactory();

        $service = ($sut)($container->reveal());

        $this->assertInstanceOf(AuthorisationClientManager::class, $service);
    }

    #[Test]
    public function it_throws_an_exception_when_not_configured(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([]);

        $sut = new AuthorisationClientManagerFactory();

        $this->expectException(RuntimeException::class);
        $service = ($sut)($container->reveal());
    }
}
