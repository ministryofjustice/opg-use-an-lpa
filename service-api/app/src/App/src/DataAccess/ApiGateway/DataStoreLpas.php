<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Exception\ApiException;
use App\DataAccess\Repository\{LpasInterface, Response, Response\LpaInterface};
use DateTimeImmutable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestFactoryInterface, StreamFactoryInterface};
use RuntimeException;

class DataStoreLpas extends AbstractApiClient implements LpasInterface
{
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        RequestSignerFactory $requestSignerFactory,
        string $apiBaseUri,
        string $traceId,
    ) {
        parent::__construct(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $requestSignerFactory,
            $apiBaseUri,
            $traceId
        );
    }

    /**
     * Looks up a Modernise LPA based on its uid.
     *
     * @param string $uid A modernise LPA uid of the format M-XXXX-XXXX-XXXX
     * @return LpaInterface|null
     * @throws ApiException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws Exception The response date header could not be transformed into a DateTimeInterface
     */
    public function get(string $uid): ?LpaInterface
    {
        $url = sprintf('%s/lpa/%s', $this->apiBaseUri, $uid);

        $signer = ($this->requestSignerFactory)(SignatureType::DataStoreLpas);

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $signer->sign($this->attachHeaders($request));

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
            StatusCodeInterface::STATUS_OK =>
                new Response\Lpa(
                    json_decode(
                        $response->getBody()->getContents(),
                        true
                    ),
                    new DateTimeImmutable($response->getHeaderLine('Date'))
                ),
            StatusCodeInterface::STATUS_NOT_FOUND => null,
            default => throw ApiException::create(
                'LPA data store returned non-ok response',
                $response,
            ),
        };
    }

    /**
     * Looks up multiple LPAs based on an array of uids.
     *
     * @param string[] $uids
     * @return LpaInterface[]
     * @throws ApiException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws Exception The response date header could not be transformed into a DateTimeInterface
     */
    public function lookup(array $uids): array
    {
        $url = sprintf('%s/lpas', $this->apiBaseUri);

        // TODO this identifier needs to come from somewhere
        $signer = ($this->requestSignerFactory)(SignatureType::DataStoreLpas, "UniqueUserIdentifier");

        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withBody(
                $this->streamFactory->createStream(
                    json_encode(['uids' => $uids])
                )
            );
        $request = $signer->sign($this->attachHeaders($request));

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
            StatusCodeInterface::STATUS_OK =>
                array_map(
                    fn ($lpaData) => new Response\Lpa(
                        $lpaData,
                        new DateTimeImmutable($response->getHeaderLine('Date'))
                    ),
                    json_decode(
                        $response->getBody()->getContents(),
                        true
                    )['lpas'],
                ),
            default => throw ApiException::create(
                'LPA data store returned non-ok response',
                $response,
            ),
        };
    }
}
