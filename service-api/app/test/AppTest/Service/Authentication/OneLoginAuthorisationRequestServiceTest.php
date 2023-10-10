<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\OneLoginAuthorisationRequestService;
use App\Service\Authentication\JWKFactory;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\IssuerBuilderInterface;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class OneLoginAuthorisationRequestServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|JWKFactory $JWKFactory;
    private ObjectProphecy|IssuerBuilder $issuerBuilder;

    public function setup(): void
    {
        $jwk                 = $this->prophesize(JWK::class);
        $this->JWKFactory    = $this->prophesize(JWKFactory::class);
        $this->issuerBuilder = $this->prophesize(IssuerBuilderInterface::class);
        $issuer              = $this->prophesize(IssuerInterface::class);
        $issuerMetaData      = $this->prophesize(IssuerMetadataInterface::class);

        $this->JWKFactory->__invoke()->willReturn($jwk);
        $issuer->getMetadata()->willReturn($issuerMetaData);
        $issuerMetaData->getAuthorizationEndpoint()->willReturn('fake endpoint');
        $this->issuerBuilder->build('http://mock-one-login:8080/.well-known/openid-configuration')->willReturn($issuer);
    }

    /**
     * @test
     */
    public function create_authorisation_request(): void
    {
        $authorisationRequestService = new OneLoginAuthorisationRequestService(
            $this->JWKFactory->reveal(),
            $this->issuerBuilder->reveal()
        );
        $authorisationRequest        = $authorisationRequestService->createAuthorisationRequest('en');
        $this->assertStringContainsString('client_id=client-id', $authorisationRequest);
        $this->assertStringContainsString('scope=openid+email', $authorisationRequest);
        $this->assertStringContainsString('vtr=%5B%22Cl.Cm.P2%22%5D', $authorisationRequest);
        $this->assertStringContainsString('ui_locales=en', $authorisationRequest);
    }
}
