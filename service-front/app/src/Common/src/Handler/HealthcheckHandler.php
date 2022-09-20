<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\ApiClient\Client as ApiClient;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthcheckHandler implements RequestHandlerInterface
{
    protected ApiClient $apiClient;

    public function __construct(protected string $version, ApiClient $api)
    {
        $this->apiClient = $api;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'overall_healthy' => $this->isHealthy(),
            'version'         => $this->version,
            'dependencies'    => $this->checkDependencyEndpoints(),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    protected function isHealthy(): bool
    {
        if ($this->checkDependencyEndpoints()['healthy']) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkDependencyEndpoints(): array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->apiClient->httpGet('/healthcheck');
        } catch (Exception) {
            $data['healthy'] = false;
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
