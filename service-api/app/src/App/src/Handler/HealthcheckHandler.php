<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\ApiGateway\RequestSigner;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use App\DataAccess\Repository\ActorUsersInterface;

/**
 * Class HealthcheckHandler
 * @package App\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    /**
     * @var string
     */
    protected $version;

    /**
     * @var ActorUsersInterface
     */
    private $actorUsers;

    /**
     * @var string
     */
    private string $apiBaseUri;

    /**
     * @var RequestSigner
     */
    private RequestSigner $awsSignature;

    /**
     * @var HttpClient
     */
    private HttpClient $httpClient;

    public function __construct(
        string $version,
        ActorUsersInterface $actorUsers,
        HttpClient $httpClient,
        RequestSigner $awsSignature,
        string $apiUrl
    ) {
        $this->version = $version;
        $this->actorUsers = $actorUsers;
        $this->httpClient = $httpClient;
        $this->awsSignature = $awsSignature;
        $this->apiBaseUri = $apiUrl;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            "version" => $this->version,
            "sirius_api" => $this->checkApiEndpoint(),
            "dynamo" => $this->checkDynamoEndpoint(),
            "lpa_codes_api" => $this->checkLpaCodesApiEndpoint(),
            "healthy" => $this->isHealthy()
        ]);
    }

    protected function isHealthy(): bool
    {
        return $this->checkDynamoEndpoint()['healthy']
        && $this->checkApiEndpoint()['healthy']
        && $this->checkLpaCodesApiEndpoint()['healthy'];
    }

    protected function checkApiEndpoint(): array
    {
        $data = [];

        $start = microtime(true);

        $url  = sprintf("%s/v1/healthcheck", $this->apiBaseUri);

        $request = new Request('GET', $url);
        $request = $this->awsSignature->sign($request);

        try {
            $response = $this->httpClient->send($request);

            if ($response->getStatusCode() === 200) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }
        } catch (GuzzleException $ge) {
            $data['healthy'] = false;
        }

        $data['response_time'] = round(microtime(true) - $start, 3);
        return $data;
    }

    protected function checkDynamoEndpoint(): array
    {
        $data = [];

        $start = microtime(true);

        try {
            $dbTables = $this->actorUsers->get('XXXXXXXXXXXX');

            if (is_array($dbTables)) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }
        } catch (Exception $e) {
            if ($e->getMessage() === 'User not found') {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
                $data['message'] = $e->getMessage();
            }
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }

    public function checkLpaCodesApiEndpoint(): array
    {
        $data = [];

        $start = microtime(true);

        $url  = sprintf("%s/v1/healthcheck", $this->apiBaseUri);

        $request = new Request('GET', $url);
        $request = $this->awsSignature->sign($request);

        try {
            $response = $this->httpClient->send($request);

            if ($response->getStatusCode() === 200) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }
        } catch (GuzzleException $ge) {
            $data['healthy'] = false;
        }

        $data['response_time'] = round(microtime(true) - $start, 3);
        return $data;
    }
}
