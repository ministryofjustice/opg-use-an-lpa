<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;

/**
 * Class HealthcheckHandler
 * @package Viewer\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    protected $httpClient;

    public function __construct(HttpClient $http)
    {
        $this->httpClient = $http;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new JsonResponse([
            "healthy" => $this->isHealthy(),
            "version" => getenv("CONTAINER_VERSION") ? getenv("CONTAINER_VERSION") : "dev",
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
        $data = [
            'healthy' => false
        ];

        $request = new Request('GET', getenv('API_SERVICE_URL').'/healthcheck');

        $start = microtime(true);

        try {
            $response = $this->httpClient->sendRequest($request);

            if (round($response->getStatusCode(), -2) == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
            }
        } catch (\Exception $e) {
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
