<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\JWSPayload\DataStoreLpas;
use App\Exception\RequestSigningException;
use App\Service\Secrets\LpaDataStoreSecretManager;
use Aws\Signature\SignatureV4;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RequestSignerFactory
{
    public function __construct(readonly private ContainerInterface $container)
    {
    }

    /**
     * Variadic function that passes any additionally provided data to the signature implementation.
     *
     * ```
     * // Fetch a signer for data store LPAs
     * $dataStoreLpaSigner = ($requestSignerFactory)(SignatureType::DataStoreLpas, 'user_identifing_string');
     * ```
     *
     * @param SignatureType|null $signature
     * @param mixed ...$additionalSignatureData Additional arguments to be passed to the signature
     *                                          implementation
     * @return RequestSigner
     * @throws RequestSigningException
     */
    public function __invoke(?SignatureType $signature = null, ...$additionalSignatureData): RequestSigner
    {
        try {
            $config = $this->container->get('config');

            $additionalHeaders = match ($signature) {
                SignatureType::ActorCodes => $this->actorCodesHeaders(),
                SignatureType::DataStoreLpas => $this->dataStoreLpasHeaders(...$additionalSignatureData),
                default => [],
            };

            $aws_region = $config['aws']['ApiGateway']['endpoint_region'] ?? 'eu-west-1';

            return new RequestSigner(new SignatureV4('execute-api', $aws_region), $additionalHeaders);
        } catch (ContainerExceptionInterface $e) {
            throw new RequestSigningException('Failed to sign request', 0, $e);
        }
    }

    /**
     * @return array{
     *     Authorization: ?string
     * }
     * @throws ContainerExceptionInterface
     */
    private function actorCodesHeaders(): array
    {
        $config = $this->container->get('config');

        return [
            'Authorization' => $config['codes_api']['static_auth_token'] ?? null,
        ];
    }

    /**
     * @return array{
     *     X-Jwt-Authorization: string
     * }
     * @throws ContainerExceptionInterface
     */
    private function dataStoreLpasHeaders(string $userIdentifier): array
    {
        $secretManager = $this->container->get(LpaDataStoreSecretManager::class);
        $jwtGenerator  = $this->container->get(GenerateJWT::class);
        $jwsPayload    = new DataStoreLpas($userIdentifier);

        return [
            'X-Jwt-Authorization' => 'Bearer ' . ($jwtGenerator)($secretManager, $jwsPayload),
        ];
    }
}
