<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaDataFormatter;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class DataStoreLpasFactory
{
    public function __invoke(ContainerInterface $container): DataStoreLpas
    {
        $config = $container->get('config');

        if (!isset($config['lpa_data_store_api']['endpoint'])) {
            throw new Exception('LPA data store API endpoint is not set');
        }

        return new DataStoreLpas(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $container->get(LpaDataFormatter::class),
            $config['lpa_data_store_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
            $container->get(LoggerInterface::class),
        );
    }
}
