<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\IssuerBuilder;
use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use PHPUnit\Framework\TestCase;

class IssuerBuilderTest extends TestCase
{
    private IssuerBuilder $issuerBuilder;

    public function setUp(): void
    {
        $this->issuerBuilder = new IssuerBuilder();
    }

    /** @test */
    public function can_set_metadata_provider_builder(): void
    {
        $issuerBuilder = $this->issuerBuilder->setMetadataProviderBuilder(new MetadataProviderBuilder());
        self::assertInstanceOf(IssuerBuilder::class, $issuerBuilder);
    }

    /** @test */
    public function can_set_jwks_provider_builder(): void
    {
        $issuerBuilder = $this->issuerBuilder->setJwksProviderBuilder(new JwksProviderBuilder());
        self::assertInstanceOf(IssuerBuilder::class, $issuerBuilder);
    }
}
