<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Exception\AuthorisationServiceException;
use App\Service\Cache\CacheFactory;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilderInterface;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;

use function Facile\OpenIDClient\base64url_encode;

class OneLoginAuthenticationRequestService
{
    public function __construct(
        private JWKFactory $jwkFactory,
        private IssuerBuilderInterface $issuerBuilder,
        private CacheFactory $cacheFactory,
    ) {
    }

    /**
    * @throws AuthorisationServiceException
     */
    public function createAuthenticationRequest(string $uiLocale, string $redirectURL): array
    {

        $cachedBuilder = new MetadataProviderBuilder();
        $cachedBuilder->setCache(($this->cacheFactory)('one-login'))
        ->setCacheTtl(3600);

        $issuer = $this->issuerBuilder
            ->setMetadataProviderBuilder($cachedBuilder)
            ->build('http://mock-one-login:8080/.well-known/openid-configuration');


        $clientMetadata = ClientMetadata::fromArray([
            'client_id'                  => 'client-id',
            'client_secret'              => 'my-client-secret',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks'                       => [
                'keys' => [
                    ($this->jwkFactory)(),
                ],
            ],
                                                    ]);

        $client = (new ClientBuilder())
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->build();

        $authorisationService = (new AuthorisationServiceBuilder())->build();

        $state                   = base64url_encode(random_bytes(12));
        $nonce                   = openssl_digest(random_bytes(24), 'sha256');
        $authorisationRequestUrl = $authorisationService->getAuthorisationUri(
            $client,
            [
                'scope'        => 'openid email',
                'state'        => $state,
                'redirect_uri' => $redirectURL,
                'nonce'        => $nonce,
                'vtr'          => '["Cl.Cm.P2"]',
                'ui_locales'   => $uiLocale,
                'claims'       => '{"userinfo":{"https://vocab.account.gov.uk/v1/coreIdentityJWT": null}}',
            ]
        );

        return [
            'state' => $state,
            'nonce' => $nonce,
            'url'   => $authorisationRequestUrl,
        ];
    }
}