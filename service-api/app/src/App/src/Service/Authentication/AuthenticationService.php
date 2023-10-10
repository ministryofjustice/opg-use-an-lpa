<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Exception;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilderInterface;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Psr\Log\LoggerInterface;

use RuntimeException;

use function Facile\OpenIDClient\base64url_encode;

class AuthenticationService
{
    public function __construct(
        private JWKFactory $JWKFactory,
        private LoggerInterface $logger,
        private IssuerBuilderInterface $issuerBuilder,
    ) {
    }

    public function redirect(string $uiLocale): string
    {
        //TODO UML-3080 Configure cache

        $issuer = $this->issuerBuilder
            ->build('http://mock-one-login:8080/.well-known/openid-configuration');


        $clientMetadata = ClientMetadata::fromArray([
            'client_id'                  => 'client-id',
            'client_secret'              => 'my-client-secret',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'redirect_uri'               => '/lpa/dashboard',
            'jwks'                       => [
                'keys' => [
                    ($this->JWKFactory)(),
                ],
            ],
                                                    ]);

        $client = (new ClientBuilder())
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->build();

        $authorisationService = (new AuthorizationServiceBuilder())->build();

        $redirectAuthorisationUri = '';
        try {
            $redirectAuthorisationUri = $authorisationService->getAuthorizationUri(
                $client,
                [
                    'scope'      => 'openid email',
                    'state'      => base64url_encode(random_bytes(12)),
                    'nonce'      => openssl_digest(base64url_encode(random_bytes(12)), 'sha256'),
                    'vtr'        => '["Cl.Cm.P2"]',
                    'ui_locales' => $uiLocale,
                    'claims'     => '{"userinfo":{"https://vocab.account.gov.uk/v1/coreIdentityJWT": null}}',
                ]
            );
        } catch (Exception $e) {
            $this->logger->error('Unable to get authorisation uri: ' . $e->getMessage());
            throw new RuntimeException('Could not create authorisation uri');
        }

        return $redirectAuthorisationUri;
    }
}
