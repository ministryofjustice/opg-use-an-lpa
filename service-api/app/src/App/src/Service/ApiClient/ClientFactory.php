<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class ClientFactory
{   
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if ( ! array_key_exists('sirius_api', $config)) {
            throw new \Exception('Sirius API configuration not present');
        }

        if ( ! array_key_exists('aws', $config)) {
            throw new \Exception('AWS configuration not present');
        }

        return new Client(
            $container->get(ClientInterface::class),
            $config['sirius_api']['endpoint'],
            $config['aws']['region']
        );
    }
}