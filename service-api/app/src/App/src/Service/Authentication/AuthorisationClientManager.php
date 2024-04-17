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
use Laminas\Cache\Psr\SimpleCache\SimpleCacheException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use RuntimeException;

class AuthorisationClientManager
{
    public const CACHE_TTL = 3600;

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

    /**
     * @throws NotFoundExceptionInterface
     * @throws SimpleCacheException
     * @throws ContainerExceptionInterface
     * @throws RuntimeException
     */
    public function get(): ClientInterface
    {
        $cachedBuilder = new MetadataProviderBuilder();
        $cachedBuilder
            ->setHttpClient($this->httpClient)
            ->setCache(($this->cacheFactory)('one-login'))
            ->setCacheTtl(self::CACHE_TTL);

        $cachedProvider = new JwksProviderBuilder();
        $cachedProvider
            ->setHttpClient($this->httpClient)
            ->setCache(($this->cacheFactory)('one-login'))
            ->setCacheTtl(self::CACHE_TTL);

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
            ->setHttpClient($this->httpClient)
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->setJwksProvider($jwksProvider->build())
            ->build();
    }
}
