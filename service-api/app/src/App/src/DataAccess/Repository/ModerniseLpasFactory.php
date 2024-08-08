<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\Service\Log\RequestTracing;
use Exception;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ModerniseLpasFactory
{
    public function __invoke(ContainerInterface $container): ModerniseLpas
    {
        $config = $container->get('config');

        if (!isset($config['lpa_data_store_api']['endpoint'])) {
            throw new Exception('LPA data store API endpoint is not set');
        }

        $httpClient = $container->get(ClientInterface::class);

        if (!$httpClient instanceof Client) {
            throw new Exception(
                ModerniseLpas::class . ' requires a Guzzle implementation of ' . ClientInterface::class
            );
        }

        return new ModerniseLpas(
            $httpClient,
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $config['lpa_data_store_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
            $container->get(DataSanitiserStrategy::class),
        );
    }
}
