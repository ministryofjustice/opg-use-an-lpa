<?php

declare(strict_types=1);

namespace BehatTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\Service\Log\RequestTracing;
use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;

class PactActorCodesFactory
{
    public function __invoke(ContainerInterface $container): ActorCodes
    {
        $config = $container->get('config');

        if (!isset($config['codes_api']['endpoint'])) {
            throw new \Exception('Actor codes API Gateway endpoint is not set');
        }

        return new ActorCodes(
            new HttpClient(),
            $container->get(RequestSigner::class),
            $config['codes_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
