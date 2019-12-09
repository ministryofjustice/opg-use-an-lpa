<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\DynamoDb\DynamoHydrateTrait;
use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use App\Service\ApiClient\Client as ApiClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * Class HealthcheckHandler
 * @package App\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $dbClient;

    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var string
     */
    protected $version;

    public function __construct(string $version, ApiClient $api, DynamoDbClient $dbClient)
    {
        $this->apiClient = $api;
        $this->version = $version;
        $this->dbClient = $dbClient;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new JsonResponse([
            "version" => $this->version,
            "dependencies" => [
                "api" => $this->checkApiEndpoint(),
                "dynamo" => $this->checkDynamoEndpoint()
            ],
            "healthy" => $this->isHealthy()
        ]);
    }

    protected function isHealthy() : bool
    {
        if ($this->checkDynamoEndpoint()['healthy'])
        {
            return true;
        } else {
            return false;
        }

    }

    protected function checkApiEndpoint() : array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->apiClient->httpGet('/lpas/700000000000');

            // TODO fix up with actual check
            // when $data == null a 404 has been returned from the api
            if (!is_null($data)) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }

        } catch (Exception $e) {
            $data['healthy'] = false;
            $data['message'] = $e->getMessage();
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }

    protected function checkDynamoEndpoint() : array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->dbClient->listTables();

            if (!empty($data["TableNames"])) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }

        } catch (Exception $e) {
            $data['healthy'] = false;
            $data['message'] = $e->getMessage();
        }

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
