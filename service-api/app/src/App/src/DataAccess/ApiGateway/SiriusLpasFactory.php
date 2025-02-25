<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaDataFormatter;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Exception;

class SiriusLpasFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['sirius_api']['endpoint'])) {
            throw new Exception('Sirius API Gateway endpoint is not set');
        }

        $httpClient = $container->get(ClientInterface::class);

        if (! $httpClient instanceof Client) {
            throw new Exception(
                SiriusLpas::class . ' requires a Guzzle implementation of ' . ClientInterface::class
            );
        }

        return new SiriusLpas(
            $httpClient,
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $config['sirius_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
            $container->get(SiriusLpaSanitiser::class),
            $container->get(LoggerInterface::class),
            $container->get(FeatureEnabled::class),
            $container->get(LpaDataFormatter::class),
        );
    }
}
