<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class ClientFactory
{
    public function __invoke(ContainerInterface $container): ClientInterface
    {
        return new Client();
    }
}
