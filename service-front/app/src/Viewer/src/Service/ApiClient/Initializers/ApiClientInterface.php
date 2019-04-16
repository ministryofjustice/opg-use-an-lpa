<?php

namespace Viewer\Service\ApiClient\Initializers;

use Viewer\Service\ApiClient\Client as ApiClient;

/**
 * Interface ApiClientInterface
 * @package Viewer\Service\ApiClient
 */
interface ApiClientInterface
{
    /**
     * @param ApiClient $client
     */
    public function setApiClient(ApiClient $client);

    /**
     * @return ApiClient
     */
    public function getApiClient() : ApiClient;
}
