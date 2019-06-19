<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class ClientFactory
{
    /**
     * @param ContainerInterface $container
     * @return SignedRequestClient
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if ( ! isset($config['sirius_api']['endpoint'])) {
            throw new Exception('Sirius API configuration not present');
        }

        if ( ! isset($config['aws']['region'])) {
            throw new Exception('AWS configuration not present');
        }

        return new SignedRequestClient(
            $container->get(ClientInterface::class),
            $config['sirius_api']['endpoint'],
            $config['aws']['region']
        );
    }
}