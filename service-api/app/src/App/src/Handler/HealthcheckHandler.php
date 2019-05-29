<?php

declare(strict_types=1);

namespace App\Handler;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Exception;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Client\ClientInterface;

/**
 * Class HealthcheckHandler
 * @package App\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    protected $httpClient;

    public function __construct(ClientInterface $http)
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
        $request = new HttpRequest('GET', 'https://api.dev.sirius.opg.digital/v1/use-an-lpa/lpas/700000000000');
        $provider = CredentialProvider::defaultProvider();
        $s4 = new SignatureV4('execute-api', 'eu-west-1');
        $signed_request = $s4->signRequest($request, $provider()->wait());

        try {
            $response = $this->httpClient->sendRequest($signed_request);

            return [
                'healthy' => $response->getStatusCode() == 404,
                'code' => $response->getStatusCode(),
                'message' => (string)$response->getBody()
            ];

        } catch (Exception $e) {
        }

        return [
            'healthy' => false,
        ];
    }
}
