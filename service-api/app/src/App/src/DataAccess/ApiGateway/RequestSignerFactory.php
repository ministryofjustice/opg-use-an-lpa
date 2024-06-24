<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\JWSPayload\DataStoreLpas;
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
     * Variadic function that passes any additionally provided data to the signature implementation
     *
     * @param SignatureType|null $signature
     * @param mixed ...$additionalSignatureData Additional arguments to be passed to the signature
     *                                          implementation
     * @return RequestSigner
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(?SignatureType $signature = null, ...$additionalSignatureData): RequestSigner
    {
        $config = $this->container->get('config');

        $additionalHeaders = match ($signature) {
            SignatureType::ActorCodes => $this->actorCodesHeaders(),
            SignatureType::DataStoreLpas => $this->dataStoreLpasHeaders(...$additionalSignatureData),
            default => [],
        };

        $aws_region = $config['aws']['ApiGateway']['endpoint_region'] ?? 'eu-west-1';

        return new RequestSigner(new SignatureV4('execute-api', $aws_region), $additionalHeaders);
    }

    /**
     * @return array{
     *     Authorization: ?string
     * }
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @throws NotFoundExceptionInterface
     */
    private function dataStoreLpasHeaders(LpaDataStoreSecretManager $secretManager, string $userIdentifier): array
    {
        $jwtGenerator = $this->container->get('GenerateJWT');
        $jwsPayload   = new DataStoreLpas($userIdentifier);

        return [
            'X-Jwt-Authorization' => ($jwtGenerator)($secretManager, $jwsPayload),
        ];
    }
}
