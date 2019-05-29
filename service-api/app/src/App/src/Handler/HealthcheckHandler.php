<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use Http\Client\HttpClient;

/**
 * Class HealthcheckHandler
 * @package App\Handler
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
                "api_gateway" => $this->checkApiEndpoint()
            ]
        ]);
    }

    protected function isHealthy() : bool
    {
        // TODO actual checks that verify service health
        return true;
    }

    protected function checkApiEndpoint() : array
    {
        // TODO actual checks of Sirius API gateway
        return [
            'healthy' => true,
            'response_time' => 0.235
        ];
    }
}
