<?php

namespace Viewer\Service\ApiClient;

use Http\Client\HttpClient;
use Interop\Container\ContainerInterface;

/**
 * Class ClientFactory
 * @package Viewer\Service\ApiClient
 */
class ClientFactory
{
    /**
     * @param ContainerInterface $container
     * @return Client
     */
    public function __invoke(ContainerInterface $container)
    {
        $httpClient = $container->get(HttpClient::class);

        $apiUri = 'http://dummy.uml.api'; //    TODO - To be added and placed in env config

        $token = null;  //  TODO - Get the token from the session or similar if required

        return new Client($httpClient, $apiUri, $token);
    }
}
