<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\AuthorisationClientManager;
use App\Service\Authentication\IssuerBuilder;
use App\Service\Authentication\JWKFactory;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use App\Service\Cache\CacheFactory;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @coversDefaultClass \App\Service\Authentication\AuthorisationClientManager
 */
class AuthorisationClientManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|CacheFactory $cacheFactory;
    private ObjectProphecy|ClientInterface $httpClient;
    private ObjectProphecy|IssuerBuilder $issuerBuilder;
    private ObjectProphecy|JWKFactory $jwkFactory;
    private ObjectProphecy|KeyPairManagerInterface $keyPairManager;

    public function setUp(): void
    {
        $this->httpClient     = $this->prophesize(ClientInterface::class);
        $this->cacheFactory   = $this->prophesize(CacheFactory::class);
        $this->issuerBuilder  = $this->prophesize(IssuerBuilder::class);
        $this->jwkFactory     = $this->prophesize(JWKFactory::class);
        $this->keyPairManager = $this->prophesize(KeyPairManagerInterface::class);
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function it_can_be_instantiated(): void
    {
        $sut = new AuthorisationClientManager(
            'fakeClientId',
            'http://fakeUrl',
            $this->jwkFactory->reveal(),
            $this->keyPairManager->reveal(),
            $this->issuerBuilder->reveal(),
            $this->cacheFactory->reveal(),
            $this->httpClient->reveal(),
        );

        $this->assertInstanceOf(AuthorisationClientManager::class, $sut);
    }

    #[Test]
    public function it_gets_a_client(): void
    {
        $this->cacheFactory
            ->__invoke('one-login')
            ->willReturn($this->prophesize(CacheInterface::class)->reveal());

        $issuer = $this->prophesize(IssuerInterface::class)->reveal();

        $this->issuerBuilder
            ->setMetadataProviderBuilder(Argument::any())
            ->willReturn($this->issuerBuilder->reveal());
        $this->issuerBuilder
            ->setJwksProviderBuilder(Argument::any())
            ->willReturn($this->issuerBuilder->reveal());
        $this->issuerBuilder
            ->build('http://fakeUrl')
            ->willReturn($issuer);

        $jwk = $this->prophesize(JWK::class);
        $jwk->jsonSerialize()->willReturn([]);

        $this->jwkFactory
            ->__invoke($this->keyPairManager->reveal())
            ->willReturn($jwk->reveal());

        $httpClient = $this->httpClient->reveal();

        $sut = new AuthorisationClientManager(
            'fakeClientId',
            'http://fakeUrl',
            $this->jwkFactory->reveal(),
            $this->keyPairManager->reveal(),
            $this->issuerBuilder->reveal(),
            $this->cacheFactory->reveal(),
            $httpClient,
        );

        $result = $sut->get();

        $this->assertSame($result->getHttpClient(), $httpClient);
        $this->assertSame($result->getIssuer(), $issuer);
    }
}
