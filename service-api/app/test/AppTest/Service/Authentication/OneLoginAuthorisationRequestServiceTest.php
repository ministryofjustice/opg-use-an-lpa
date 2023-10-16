<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\OneLoginAuthorisationRequestService;
use App\Service\Authentication\JWKFactory;
use App\Service\Cache\CacheFactory;
use App\Service\Authentication\IssuerBuilder;
use Facile\OpenIDClient\Issuer\IssuerBuilderInterface;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Interop\Container\Containerinterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\SimpleCache\CacheInterface;

class OneLoginAuthorisationRequestServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|JWKFactory $jwkFactory;
    private ObjectProphecy|IssuerBuilder $issuerBuilder;
    private ObjectProphecy|CacheFactory $cacheFactory;

    public function setup(): void
    {
        $jwk                 = $this->prophesize(JWK::class);
        $this->jwkFactory    = $this->prophesize(JWKFactory::class);
        $this->issuerBuilder = $this->prophesize(IssuerBuilder::class);
        $issuer              = $this->prophesize(IssuerInterface::class);
        $issuerMetaData      = $this->prophesize(IssuerMetadataInterface::class);
        $this->cacheFactory  = $this->prophesize(CacheFactory::class);
        $cacheInterface      = $this->prophesize(CacheInterface::class);

        $this->jwkFactory->__invoke()->willReturn($jwk);
        $issuer->getMetadata()->willReturn($issuerMetaData);
        $issuerMetaData->getAuthorizationEndpoint()->willReturn('fake endpoint');
        $this->issuerBuilder->setMetadataProviderBuilder(Argument::any())->willReturn($this->issuerBuilder);
        $this->issuerBuilder->build('http://mock-one-login:8080/.well-known/openid-configuration')->willReturn($issuer);
        $this->cacheFactory->__invoke('cache')->willReturn($cacheInterface);
    }

    /**
     * @test
     */
    public function create_authorisation_request(): void
    {
        $authorisationRequestService = new OneLoginAuthorisationRequestService(
            $this->jwkFactory->reveal(),
            $this->issuerBuilder->reveal(),
            $this->cacheFactory->reveal(),
        );
        $authorisationRequest        = $authorisationRequestService->createAuthorisationRequest('en');
        $this->assertStringContainsString('client_id=client-id', $authorisationRequest);
        $this->assertStringContainsString('scope=openid+email', $authorisationRequest);
        $this->assertStringContainsString('vtr=%5B%22Cl.Cm.P2%22%5D', $authorisationRequest);
        $this->assertStringContainsString('ui_locales=en', $authorisationRequest);
        $this->assertStringContainsString('redirect_uri=%2Flpa%2Fdashboard', $authorisationRequest);
    }
}
