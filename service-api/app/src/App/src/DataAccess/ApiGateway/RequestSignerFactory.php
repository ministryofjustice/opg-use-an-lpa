<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Aws\Signature\SignatureV4;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RequestSignerFactory
{
    public function __construct(readonly private ContainerInterface $container)
    {
    }

    public function __invoke(?SignatureType $signature = null): RequestSigner
    {
        $config = $this->container->get('config');

        $additionalHeaders = match ($signature) {
            SignatureType::ActorCodes => $this->actorCodesHeaders(),
            SignatureType::DataStoreLpas => $this->dataStoreLpasHeaders(),
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
     */
    private function dataStoreLpasHeaders(): array
    {
        return [
            'X-Jwt-Authorization' => null,
        ];
    }
}
