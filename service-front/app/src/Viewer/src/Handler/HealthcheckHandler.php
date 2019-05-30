<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use GuzzleHttp\Psr7\Request as HttpRequest;
use \Exception;
use Viewer\Service\ApiClient\Client as ApiClient;

/**
 * Class HealthcheckHandler
 * @package Viewer\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @var string
     */
    protected $version;

    public function __construct(string $version, ApiClient $api)
    {
        $this->apiClient = $api;
        $this->version = $version;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new JsonResponse([
            "healthy" => $this->isHealthy(),
            "version" => $this->version,
            "dependencies" => [
                "api" => $this->checkApiEndpoint()
            ]
        ]);
    }

    protected function isHealthy() : bool
    {
        return true;
    }

    protected function checkApiEndpoint() : array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->apiClient->httpGet('/healthcheck');
        } catch (Exception $e) {
            $data['healthy'] = false;
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
