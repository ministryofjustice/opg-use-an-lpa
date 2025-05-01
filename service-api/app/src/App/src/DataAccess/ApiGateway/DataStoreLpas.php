<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Exception\ApiException;
use App\Exception\OriginatorIdNotSetException;
use App\Service\Lpa\LpaDataFormatter;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use App\DataAccess\Repository\{AuditableLpasInterface, Response, Response\LpaInterface};
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestFactoryInterface, ResponseInterface, StreamFactoryInterface};
use RuntimeException;
use Throwable;
use Psr\Log\LoggerInterface;

class DataStoreLpas extends AbstractApiClient implements AuditableLpasInterface
{
    private string $originatorId;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        RequestSignerFactory $requestSignerFactory,
        private LpaDataFormatter $lpaDataFormatter,
        string $apiBaseUri,
        string $traceId,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $requestSignerFactory,
            $apiBaseUri,
            $traceId,
        );
    }

    public function setOriginatorId(string $originatorId): AuditableLpasInterface
    {
        $this->originatorId = $originatorId;

        return $this;
    }

    public function get(string $uid): ?LpaInterface
    {
        $url = sprintf('%s/lpas/%s', $this->apiBaseUri, $uid);

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $this->createRequestSigner()->sign($this->attachHeaders($request));

        try {
            $response = $this->httpClient->sendRequest($request);

            $this->logger->info(
                'Response from DataStoreLPas M-LPAs  from upstream {lpas}',
                [
                    'lpas' => $response->getStatusCode(),
                ],
            );
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create(
                'Error whilst communicating with LPA data store',
                null,
                $ce,
            );
        }

        return match ($response->getStatusCode()) {
            StatusCodeInterface::STATUS_OK => $this->formatSingleLpaResponse($response),
            StatusCodeInterface::STATUS_NOT_FOUND => null,
            default => throw ApiException::create(
                'LPA data store returned non-ok response',
                $response,
            ),
        };
    }

    public function lookup(array $uids): array
    {
        $url = sprintf('%s/lpas', $this->apiBaseUri);

        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withBody(
                $this->streamFactory->createStream(
                    json_encode(['uids' => $uids])
                )
            );
        $request = $this->createRequestSigner()->sign($this->attachHeaders($request));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create(
                'Error whilst communicating with LPA data store',
                null,
                $ce,
            );
        }

        return match ($response->getStatusCode()) {
            StatusCodeInterface::STATUS_OK => $this->formatMultipleLpaResponse($response),
            default => throw ApiException::create(
                'LPA data store returned non-ok response',
                $response,
            ),
        };
    }

    /**
     * @return RequestSigner
     * @throws ApiException
     */
    private function createRequestSigner(): RequestSigner
    {
        try {
            /** @psalm-suppress RedundantPropertyInitializationCheck */
            if (!isset($this->originatorId)) {
                throw new OriginatorIdNotSetException(
                    'Unable to create a request signer for this endpoint without an originator id'
                );
            }

            return ($this->requestSignerFactory)(SignatureType::DataStoreLpas, $this->originatorId);
        } catch (OriginatorIdNotSetException | NotFoundExceptionInterface | ContainerExceptionInterface $exception) {
            throw ApiException::create(
                'Unable to build a request signer instance',
                null,
                $exception,
            );
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Response\Lpa
     * @throws ApiException
     */
    private function formatSingleLpaResponse(ResponseInterface $response): Response\Lpa
    {
        try {
            return new Response\Lpa(
                ($this->lpaDataFormatter)(
                    json_decode(
                        $response->getBody()->getContents(),
                        true,
                    ),
                ),
                new DateTimeImmutable($response->getHeaderLine('Date'))
            );
        } catch (UnableToHydrateObject | RuntimeException | Throwable $exception) {
            throw ApiException::create(
                'Not possible to create LPA from response data',
                $response,
                $exception,
            );
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Response\Lpa[]
     * @throws ApiException
     */
    private function formatMultipleLpaResponse(ResponseInterface $response): array
    {
        try {
            $lpas = json_decode(
                $response->getBody()->getContents(),
                true
            )['lpas'];
        } catch (RuntimeException $exception) {
            throw ApiException::create(
                'Not possible to create LPA from response data',
                null,
                $exception,
            );
        }

        $result = [];
        foreach ($lpas as $lpa) {
            try {
                $result[] = new Response\Lpa(
                    ($this->lpaDataFormatter)($lpa),
                    new DateTimeImmutable($response->getHeaderLine('Date'))
                );
            } catch (UnableToHydrateObject | Throwable) {
                // if one lpa out of many breaks we should still attempt to return those unbroken ones
            }
        }

        return $result;
    }
}
