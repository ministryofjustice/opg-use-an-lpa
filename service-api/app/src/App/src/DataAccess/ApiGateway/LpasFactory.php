<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Client as HttpClient;
use Aws\Signature\SignatureV4;

class LpasFactory
{

    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['sirius_api']['endpoint'])) {
            throw new \Exception('Sirius API Gateway endpoint is not set');
        }

        return new Lpas(
            $container->get(HttpClient::class),
            new SignatureV4('execute-api', 'eu-west-1'),
            $config['sirius_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
