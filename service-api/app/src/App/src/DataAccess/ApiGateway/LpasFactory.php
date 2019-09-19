<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Psr\Container\ContainerInterface;
use GuzzleHttp\Client as HttpClient;

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
            $config['sirius_api']['endpoint']
        );
    }

}