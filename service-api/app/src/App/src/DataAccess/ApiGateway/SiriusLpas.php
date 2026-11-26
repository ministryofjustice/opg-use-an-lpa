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
use Aws\EventBridge\EventBridgeClient;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Fig\Http\Message\StatusCodeInterface;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Each;
use GuzzleHttp\Promise\PromiseInterface;
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
    private const int CONCURRENCY = 50;

    /** @psalm-var Client */
    protected readonly ClientInterface $httpClient;

    public function __construct(
        Client $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        RequestSignerFactory $requestSignerFactory,
        string $apiBaseUri,
        private readonly string $eventBusName,
        private readonly DataSanitiserStrategy $sanitiser,
        private readonly LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
        private LpaDataFormatter $lpaDataFormatter,
        private EventBridgeClient $eventBridgeClient,
    ) {
        parent::__construct(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $requestSignerFactory,
            $apiBaseUri,
        );
    }

    public function get(string $uid): ?LpaInterface
    {
        $result = $this->lookup([$uid]);
        return !empty($result) ? current($result) : null;
    }

    public function lookup(array $uids): array
    {
        return $this->lookupAsync($uids)->wait();
    }

    public function lookupAsync(array $uids, ?callable $onLpa = null): PromiseInterface
    {
        $signer   = $this->createRequestSigner();
        $requests = [];
        foreach (array_unique($uids) as $uid) {
            $url     = $this->apiBaseUri . sprintf('/v1/use-an-lpa/lpas/%s', $uid);
            $request = $this->attachHeaders($this->requestFactory->createRequest('GET', $url));

            $requests[] = [
                $uid,
                $signer->sign($request),
            ];
        }

        /** @var LpaInterface[] $results */
        $results = [];

        $promises = (function () use ($requests, $onLpa, &$results): Generator {
            foreach ($requests as [$uid, $request]) {
                yield $this->sendAsync($request)->then(
                    function (ResponseInterface $response) use ($uid, $onLpa, &$results): mixed {
                        $lpa = $this->handleLpaResponse($uid, $response);
                        if ($lpa === null) {
                            return null;
                        }

                        $results[$uid] = $lpa;

                        return $onLpa !== null ? $onLpa($lpa, $uid) : null;
                    },
                    function (mixed $reason) use ($uid): void {
                        $this->logger->warning(
                            'Unable to request LPA {lpaUid} from gateway',
                            [
                                'event_code' => EventCodes::UNEXPECTED_DATA_LPA_API_RESPONSE,
                                'lpaUid'     => $uid,
                                'reason'     => $reason instanceof Throwable ? $reason->getMessage() : $reason,
                            ]
                        );
                    },
                );
            }
        })();

        // Each LPA promise includes the work done by the callback so the concurrency limit applies to both
        return Each::ofLimitAll($promises, self::CONCURRENCY)
            ->then(function () use (&$results): array {
                return $results;
            });
    }

    public function requestLetter(LpaUid $caseId, ?string $actorId, ?string $additionalInfo): void
    {
        if ($caseId->getLpaSource() === LpaSource::LPASTORE) {
            $entry = [
                'Source'       => 'opg.poas.use',
                'DetailType'   => 'activation-key-requested',
                'Detail'       => json_encode(['uid' => $caseId->getLpaUid(), 'actor' => $actorId]),
                'EventBusName' => $this->eventBusName,
            ];

            $result = $this->eventBridgeClient->putEvents([
                'Entries' => [$entry],
            ]);

            if ($result->hasKey('FailedEntryCount') && $result->get('FailedEntryCount') > 0) {
                $this->logger->warning('Failed to put activation-key-requested event for LPA {lpaUid}', [
                    'lpaUid' => $caseId->getLpaUid(),
                ]);

                throw ApiException::create('Failed to put event for LPA');
            } else {
                $this->logger->info('Sent activation-key-requested event for {lpaUid}', [
                    'lpaUid' => $caseId->getLpaUid(),
                ]);
            }

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
     * @throws ApiException
     */
    private function handleLpaResponse(string $uid, ResponseInterface $response): ?Lpa
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode === StatusCodeInterface::STATUS_OK) {
            return $this->formatSingleLpaResponse($response);
        }

        $this->logger->warning(
            'Unexpected {status} response from gateway for request of LPA {lpaUid}',
            [
                'event_code' => EventCodes::UNEXPECTED_DATA_LPA_API_RESPONSE,
                'status'     => $statusCode,
                'lpaUid'     => $uid,
            ]
        );

        return null;
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
