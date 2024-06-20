<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use Psr\Container\ContainerInterface;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InstructionsAndPreferencesImagesFactory
{
    public function __invoke(ContainerInterface $container): InstructionsAndPreferencesImages
    {
        $config = $container->get('config');

        if (!isset($config['iap_images_api']['endpoint'])) {
            throw new Exception('Instructions and Preferences API Gateway endpoint is not set');
        }

        return new InstructionsAndPreferencesImages(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $config['iap_images_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
