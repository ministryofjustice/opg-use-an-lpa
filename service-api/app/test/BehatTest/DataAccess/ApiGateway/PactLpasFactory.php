<?php

declare(strict_types=1);

namespace BehatTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\DataAccess\Repository\LpasInterface;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\RequestTracing;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class PactLpasFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container): LpasInterface
    {
        $config = $container->get('config');

        if (!isset($config['sirius_api']['endpoint'])) {
            throw new NotFoundException('Sirius API Gateway endpoint is not set');
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
            parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST),
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
            $container->get(SiriusLpaSanitiser::class),
            $container->get(LoggerInterface::class),
            $container->get(FeatureEnabled::class)
        );
    }
}
