<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\ActorUsersInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class HealthcheckHandler
 * @package App\Handler
 */
final class HealthcheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $version,
        private ActorUsersInterface $actorUsers,
        private HttpClient $httpClient,
        private RequestSigner $awsSignature,
        private string $siriusApiUrl,
        private string $codesApiUrl,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'version'       => $this->version,
            'lpa_api'       => $this->stopwatch($this->checkApiEndpoint(...)),
            'dynamo'        => $this->stopwatch($this->checkDynamoEndpoint(...)),
            'lpa_codes_api' => $this->stopwatch($this->checkCodesApiEndpoint(...)),
        ];

        $data['healthy'] = $this->isHealthy($data);

        return new JsonResponse($data);
    }

    private function isHealthy(array $data): bool
    {
        return $data['lpa_api']['healthy']
            && $data['dynamo']['healthy']
            && $data['lpa_codes_api']['healthy'];
    }

    private function checkApiEndpoint(): array
    {
        $url = sprintf('%s/v1/healthcheck', $this->siriusApiUrl);

        return $this->apiCall(new Request('GET', $url));
    }

    private function checkDynamoEndpoint(): array
    {
        $data = [];

        try {
            $this->actorUsers->get('XXXXXXXXXXXX');

            $data['healthy'] = true;
        } catch (Throwable $e) {
            if ($e->getMessage() === 'User not found') {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
                $data['message'] = $e->getMessage();
            }
        }

        return $data;
    }

    private function checkCodesApiEndpoint(): array
    {
        $url  = sprintf('%s/v1/healthcheck', $this->codesApiUrl);

        return $this->apiCall(new Request('GET', $url));
    }

    /**
     * @param RequestInterface $request
     *
     * @return array{healthy: bool}
     */
    private function apiCall(RequestInterface $request): array
    {
        $data = [];
        $signedRequest = $this->awsSignature->sign($request);

        try {
            $response = $this->httpClient->send($signedRequest);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }
        } catch (Throwable $e) {
            $data['healthy'] = false;
        }

        return $data;
    }

    /**
     * @param callable $functionToTime
     *
     * @return array{response_time: float}
     */
    private function stopwatch(callable $functionToTime): array
    {
        $start = microtime(true);

        $data = $functionToTime();

        $data['response_time'] = round(microtime(true) - $start, 3);

        return $data;
    }
}
