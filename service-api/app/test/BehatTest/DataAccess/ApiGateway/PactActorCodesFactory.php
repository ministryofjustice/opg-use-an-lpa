<?php

declare(strict_types=1);

namespace BehatTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\Service\Log\RequestTracing;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PactActorCodesFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ActorCodes
    {
        $config = $container->get('config');

        if (!isset($config['codes_api']['endpoint'])) {
            throw new NotFoundException('Actor codes API Gateway endpoint is not set');
        }

        return new ActorCodes(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            parse_url($config['codes_api']['endpoint'], PHP_URL_HOST),
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
