<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\RequestLetterInterface;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Enum\LpaSource;
use App\Exception\ApiException;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\EventCodes;
use App\Service\Lpa\LpaDataFormatter;
use App\Service\Lpa\SiriusLpa;
use App\Value\LpaUid;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Looks up LPAs in the Sirius API Gateway.
 */
class SiriusLpas extends AbstractApiClient implements LpasInterface, RequestLetterInterface
{
    /** @psalm-var Client */
    protected readonly ClientInterface $httpClient;

    public function __construct(
        Client $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        RequestSignerFactory $requestSignerFactory,
        string $apiBaseUri,
        string $traceId,
        private readonly DataSanitiserStrategy $sanitiser,
        private readonly LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
        private LpaDataFormatter $lpaDataFormatter,
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

    public function get(string $uid): ?LpaInterface
    {
        $result = $this->lookup([$uid]);
        return !empty($result) ? current($result) : null;
    }

    public function lookup(array $uids): array
    {
        // Builds an array of Requests to send
        // The key for each request is the original uid.
        $signer   = $this->createRequestSigner();
        $requests = array_combine(
            $uids,  // Use as array key
            array_map(function ($v) use ($signer) {
                $url     = $this->apiBaseUri . sprintf('/v1/use-an-lpa/lpas/%s', $v);
                $request = $this->requestFactory->createRequest('GET', $url);
                $request = $this->attachHeaders($request);

                return $signer->sign($request);
            }, $uids)
        );

        /** @var ResponseInterface[] $results */
        $results = [];

        $pool = new Pool(
            $this->httpClient,
            $requests,
            [
                'concurrency' => 50,
                'fulfilled'   => function (GuzzleResponse $response, int $id) use (&$results) {
                    $results[$id] = $response;
                },
                'rejected'    => function ($reason, $id) {
                    // Log?
                },
            ]
        );

        // Initiate transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete
        $promise->wait();

        // Handle all request response now
        foreach ($results as $uid => $result) {
            $statusCode = $result->getStatusCode();
            switch ($statusCode) {
                case 200:
                    $results[$uid] = $this->formatSingleLpaResponse($result);
                    break;
                default:
                    $this->logger->warning(
                        'Unexpected {status} response from gateway for request of LPA {lpaUid}',
                        [
                            'event_code' => EventCodes::UNEXPECTED_DATA_LPA_API_RESPONSE,
                            'status'     => $statusCode,
                            'lpaUid'     => $uid,
                        ]
                    );
                    unset($results[$uid]);
            }
        }

        /** @var LpaInterface[] */
        return $results;
    }

    public function requestLetter(LpaUid $caseId, ?string $actorId, ?string $additionalInfo): void
    {
        if ($caseId->getLpaSource() === LpaSource::LPASTORE) {
            $this->logger->info('TODO request a letter from Sirius');
            return;
        }

        $payloadContent = ['case_uid' => (int)$caseId->getLpaUid()];

        if ($actorId === null) {
            $payloadContent['notes'] = $additionalInfo;
        } else {
            $payloadContent['actor_uid'] = (int)$actorId;
        }

        // construct request for API gateway
        $url     = $this->apiBaseUri . '/v1/use-an-lpa/lpas/requestCode';
        $request = $this->requestFactory->createRequest('POST', $url);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payloadContent)));
        $request = $this->attachHeaders($request);
        $request = $this->createRequestSigner()->sign($request);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create('Error whilst communicating with api gateway', null, $ce);
        }

        $statusCode = $response->getStatusCode();
        if (
            $statusCode === StatusCodeInterface::STATUS_NO_CONTENT ||
            $statusCode === StatusCodeInterface::STATUS_OK
        ) {
            return;
        }
        throw ApiException::create('Letter request not successfully precessed by api gateway', $response);
    }

    /**
     * @return RequestSigner
     * @throws ApiException
     */
    private function createRequestSigner(): RequestSigner
    {
        try {
            return ($this->requestSignerFactory)();
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $exception) {
            throw ApiException::create(
                'Unable to build a request signer instance',
                null,
                $exception,
            );
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Lpa
     * @throws ApiException
     */
    private function formatSingleLpaResponse(ResponseInterface $response): Lpa
    {
        try {
            $body = json_decode(
                $response->getBody()->getContents(),
                true,
            );
            # TODO: We can some more error checking around this.
            if (($this->featureEnabled)('support_datastore_lpas')) {
                return new Lpa(
                    ($this->lpaDataFormatter)($body),
                    new DateTimeImmutable($response->getHeaderLine('Date'))
                );
            } else {
                return new Lpa(
                    new SiriusLpa(
                        $this->sanitiser->sanitise($body),
                        $this->logger,
                    ),
                    new DateTimeImmutable($response->getHeaderLine('Date'))
                );
            }
        } catch (UnableToHydrateObject | RuntimeException | Throwable $exception) {
            throw ApiException::create(
                'Not possible to create LPA from response data',
                $response,
                $exception,
            );
        }
    }
}
