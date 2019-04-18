<?php

namespace Viewer\Service\Http;

use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Interop\Container\ContainerInterface;

/**
 * Class GuzzleClientFactory
 * @package Viewer\Service\Http
 */
class GuzzleClientFactory
{
    /**
     * @param ContainerInterface $container
     * @return GuzzleClient
     */
    public function __invoke(ContainerInterface $container)
    {
        return GuzzleClient::createWithConfig([
            //  TODO - might require .... 'verify' => false
        ]);
    }
}
