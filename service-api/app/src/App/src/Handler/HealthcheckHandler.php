<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use App\DataAccess\Repository\ActorUsersInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HealthcheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly RequestSignerFactory $requestSignerFactory,
        private readonly ActorUsersInterface $actorUsers,
        private readonly string $version,
        private readonly string $siriusApiUrl,
        private readonly string $lpaStoreApiUrl,
        private readonly string $codesApiUrl,
        private readonly string $iapImagesApiUrl,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'version'        => $this->version,
            'sirius_api'     => $this->stopwatch($this->checkSiriusEndpoint(...)),
            'lpa_store_api'  => $this->stopwatch($this->checkLpaStoreEndpoint(...)),
            'dynamo'         => $this->stopwatch($this->checkDynamoEndpoint(...)),
            'lpa_codes_api'  => $this->stopwatch($this->checkCodesApiEndpoint(...)),
            'iap_images_api' => $this->stopwatch($this->checkIapImagesApi(...)),
        ];

        $data['healthy'] = $this->isHealthy($data);

        return new JsonResponse($data);
    }

    private function isHealthy(array $data): bool
    {
        return $data['sirius_api']['healthy']
            && $data['dynamo']['healthy']
            && $data['lpa_store_api']['healthy']
            && $data['lpa_codes_api']['healthy']
            && $data['iap_images_api']['healthy'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkIapImagesApi(): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/v1/healthcheck', $this->iapImagesApiUrl),
        );
        $request = ($this->requestSignerFactory)()->sign($request);

        return $this->apiCall($request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkSiriusEndpoint(): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/v1/healthcheck', $this->siriusApiUrl),
        );
        $request = ($this->requestSignerFactory)()->sign($request);

        return $this->apiCall($request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkCodesApiEndpoint(): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/v1/healthcheck', $this->codesApiUrl),
        );
        $request = ($this->requestSignerFactory)(SignatureType::ActorCodes)->sign($request);

        return $this->apiCall($request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkLpaStoreEndpoint(): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/health-check', $this->lpaStoreApiUrl),
        );
        $request = ($this->requestSignerFactory)(
            SignatureType::DataStoreLpas,
            'use-an-lpa-api-healthcheck'
        )->sign($request);

        return $this->apiCall($request);
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

    /**
     * @param RequestInterface $signedRequest
     * @return array{healthy: bool}
     */
    private function apiCall(RequestInterface $signedRequest): array
    {
        $data = [];

        try {
            $response = $this->httpClient->sendRequest($signedRequest);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $data['healthy'] = true;
            } else {
                $data['healthy'] = false;
            }
        } catch (Throwable) {
            $data['healthy'] = false;
        }

        return $data;
    }

    /**
     * @param callable $functionToTime
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
