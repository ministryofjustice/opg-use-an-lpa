<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use Psr\Container\ContainerInterface;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InstructionsAndPreferencesImagesFactory
{
    public function __invoke(ContainerInterface $container): InstructionsAndPreferencesImagesInterface
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
        );
    }
}
