<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use Psr\Container\ContainerInterface;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ActorCodesFactory
{
    public function __invoke(ContainerInterface $container): ActorCodes
    {
        $config = $container->get('config');

        if (!isset($config['codes_api']['endpoint'])) {
            throw new Exception('Actor codes API Gateway endpoint is not set');
        }

        return new ActorCodes(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $config['codes_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
