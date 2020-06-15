<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Aws\Signature\SignatureV4;
use Psr\Container\ContainerInterface;

class RequestSignerFactory
{
    public function __invoke(ContainerInterface $container): RequestSigner
    {
        $config = $container->get('config');

        $token = $config['codes_api']['static_auth_token'] ?? null;

        return new RequestSigner(new SignatureV4('execute-api', 'eu-west-1'), $token);
    }
}
