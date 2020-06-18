<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\ApiClient\Client as ApiClient;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Exception;

/**
 * Class HealthcheckHandler
 * @package Common\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    /**
     * @var ApiClient
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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            "overall_healthy" => $this->isHealthy(),
            "version" => $this->version,
            "dependencies" => $this->checkDependencyEndpoints()
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
        } catch (Exception $e) {
            $data['healthy'] = false;
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
