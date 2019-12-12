<?php

declare(strict_types=1);

namespace App\Handler;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use Aws\DynamoDb\DynamoDbClient;
use App\DataAccess\Repository\LpasInterface;

/**
 * Class HealthcheckHandler
 * @package App\Handler
 */
class HealthcheckHandler implements RequestHandlerInterface
{
    /**
     * @var DynamoDbClient
     */
    private $dbClient;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var LpasInterface
     */
    private $lpaInterface;

    public function __construct(
        string $version,
        DynamoDbClient $dbClient,
        LpasInterface $lpaInterface)
    {
        $this->version = $version;
        $this->dbClient = $dbClient;
        $this->lpaInterface = $lpaInterface;
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
        return ($this->checkDynamoEndpoint()['healthy'] && $this->checkApiEndpoint()['healthy']);
    }

    protected function checkApiEndpoint() : array
    {
        $data = [];

        $start = microtime(true);

        try {
            $data = $this->lpaInterface->get("700000000000");

            // when $data == null a 404 has been returned from the api
            if (is_null($data)) {
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
            $dbTables = $this->dbClient->listTables();

            if (count($dbTables["TableNames"]) > 1) {
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
