<?php

declare(strict_types=1);

namespace App\Handler;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\ActorCodesInterface;

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
     * @var ActorCodesInterface
     */
    private $actorCodes;

    /**
     * @var LpasInterface
     */
    private $lpaInterface;

    public function __construct(
        string $version,
        LpasInterface $lpaInterface,
        ActorCodesInterface $actorCodes
    ) {
        $this->version = $version;
        $this->lpaInterface = $lpaInterface;
        $this->actorCodes = $actorCodes;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
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

    protected function isHealthy(): bool
    {
        return ($this->checkDynamoEndpoint()['healthy'] && $this->checkApiEndpoint()['healthy']);
    }

    protected function checkApiEndpoint(): array
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

    protected function checkDynamoEndpoint(): array
    {
        $data = [];

        $start = microtime(true);

        try {
            $dbTables = $this->actorCodes->get('XXXXXXXXXXXX');

            if (is_null($dbTables)) {
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
