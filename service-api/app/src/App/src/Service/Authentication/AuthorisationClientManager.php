<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use App\Service\Cache\CacheFactory;
use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class AuthorisationClientManager
{
    public function __construct(
        private string $clientId,
        private string $clientDiscoveryEndpoint,
        private JWKFactory $jwkFactory,
        private KeyPairManagerInterface $keyPairManager,
        private IssuerBuilder $issuerBuilder,
        private CacheFactory $cacheFactory,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function get(): ClientInterface
    {
        $cachedBuilder = new MetadataProviderBuilder();
        $cachedBuilder
            ->setHttpClient($this->httpClient)
            ->setCache(($this->cacheFactory)('one-login'))
            ->setCacheTtl(3600);

        $cachedProvider = new JwksProviderBuilder();
        $cachedProvider
            ->setHttpClient($this->httpClient)
            ->setCache(($this->cacheFactory)('one-login'))
            ->setCacheTtl(3600);

        $issuer = $this->issuerBuilder
            ->setMetadataProviderBuilder($cachedBuilder)
            ->setJwksProviderBuilder($cachedProvider)
            ->build($this->clientDiscoveryEndpoint);

        $clientMetadata = ClientMetadata::fromArray(
            [
                'client_id'                  => $this->clientId,
                'token_endpoint_auth_method' => 'private_key_jwt',
            ],
        );

        $jwksProvider = new JwksProviderBuilder();
        $jwksProvider->setJwks(
            [
                'keys' => [($this->jwkFactory)($this->keyPairManager)->jsonSerialize()],
            ],
        );

        return (new ClientBuilder())
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->setJwksProvider($jwksProvider->build())
            ->build();
    }
}
