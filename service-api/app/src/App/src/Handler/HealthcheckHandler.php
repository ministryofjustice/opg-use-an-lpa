<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class HealthcheckHandler
 * @package Viewer\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
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
        return true;
    }

    protected function checkApiEndpoint() : array
    {
        return [
            "healthy" => true,
            "version" => "dev"
        ];
    }
}
