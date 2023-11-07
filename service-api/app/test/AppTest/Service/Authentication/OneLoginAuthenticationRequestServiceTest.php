<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\OneLoginAuthenticationRequestService;
use App\Service\Authentication\JWKFactory;
use App\Service\Cache\CacheFactory;
use App\Service\Authentication\IssuerBuilder;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\SimpleCache\CacheInterface;

class OneLoginAuthenticationRequestServiceTest extends TestCase
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
        $this->cacheFactory->__invoke('one-login')->willReturn($cacheInterface);
    }

    /**
     * @test
     */
    public function create_authentication_request(): void
    {
        $authorisationRequestService = new OneLoginAuthenticationRequestService(
            $this->jwkFactory->reveal(),
            $this->issuerBuilder->reveal(),
            $this->cacheFactory->reveal(),
        );
        $fakeRedirect                = 'http://fakehost/auth/redirect';
        $authorisationRequest        = $authorisationRequestService->createAuthenticationRequest('en', $fakeRedirect);
        $authorisationRequestUrl     = $authorisationRequest['url'];
        $this->assertStringContainsString('client_id=client-id', $authorisationRequestUrl);
        $this->assertStringContainsString('scope=openid+email', $authorisationRequestUrl);
        $this->assertStringContainsString('vtr=["Cl.Cm.P2"]', urldecode($authorisationRequestUrl));
        $this->assertStringContainsString('ui_locales=en', $authorisationRequestUrl);
        $this->assertStringContainsString('redirect_uri=' . $fakeRedirect, urldecode($authorisationRequestUrl));
    }
}
