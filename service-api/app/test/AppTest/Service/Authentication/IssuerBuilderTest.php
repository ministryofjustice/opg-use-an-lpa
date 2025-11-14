<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\IssuerBuilder;
use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IssuerBuilderTest extends TestCase
{
    private IssuerBuilder $issuerBuilder;

    public function setUp(): void
    {
        $this->issuerBuilder = new IssuerBuilder();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function can_set_metadata_provider_builder(): void
    {
        $_ = $this->issuerBuilder->setMetadataProviderBuilder(new MetadataProviderBuilder());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function can_set_jwks_provider_builder(): void
    {
        $_ = $this->issuerBuilder->setJwksProviderBuilder(new JwksProviderBuilder());
    }
}
