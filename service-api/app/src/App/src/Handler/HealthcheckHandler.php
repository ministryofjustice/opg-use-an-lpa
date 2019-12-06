<?php

declare(strict_types=1);

namespace App\Handler;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use App\Service\ApiClient\Client as ApiClient;

/**
 * Class HealthcheckHandler
 * @package App\Handler
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
        // TODO actual checks that verify service health
        return true;
    }

    protected function checkApiEndpoint() : array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->apiClient->httpGet('/lpas/700000000000');

            // TODO fix up with actual check
            // when $data == null a 404 has been returned from the api
            if (is_null($data)) {
                $data['healthy'] = true;
            }

            $data['healthy'] = true;
        } catch (Exception $e) {
            $data['healthy'] = false;
            $data['message'] = $e->getMessage();
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
