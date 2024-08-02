<?php

namespace App\DataAccess\Repository;

use App\DataAccess\ApiGateway\SiriusLpas;
use App\Service\Log\RequestTracing;
use Exception;
use GuzzleHttp\Client;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;

class ModerniseLpasFactory
{
    public function __invoke(ContainerInterface $container): ModerniseLpas
    {
        $config = $container->get('config');

        if (!isset($config['lpa_data_store_api']['endpoint'])) {
            throw new Exception('LPA data store API endpoint is not set');
        }

        $httpClient = $container->get(ClientInterface::class);

        if (! $httpClient instanceof Client) {
            throw new Exception(
                SiriusLpas::class . ' requires a Guzzle implementation of ' . ClientInterface::class
            );
        }

        $trace_id = $container->get(RequestTracing::TRACE_PARAMETER_NAME);

        return new ModerniseLpas(
            $httpClient,
            $trace_id,
            $config['lpa_data_store_api']['endpoint'],
            $container->get(DataSanitiserStrategy::class)
        );
}

}