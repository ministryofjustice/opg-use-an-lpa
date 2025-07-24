<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PaperVerificationCodesFactory
{
    public function __invoke(ContainerInterface $container): PaperVerificationCodes
    {
        $config = $container->get('config');

        if (!isset($config['codes_api']['endpoint'])) {
            throw new Exception('Paper verification codes API Gateway endpoint is not set');
        }

        return new PaperVerificationCodes(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $config['codes_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
