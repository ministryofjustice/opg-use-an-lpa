<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess.Repository\RequestLetterInterface;
use App\DataAccess\Repository\Response;
use App\Exception\ApiException;
use App\Service\Log\EventCodes;
use DateTimeImmutable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

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

    /**
     * Looks up an LPA based on its Sirius uid.
     *
     * @param string $uid
     *
     * @return Response\LpaInterface|null
     * @throws Exception
     */
    public function get(string $uid): ?Response\LpaInterface
    {
        $result = $this->lookup([$uid]);
        return !empty($result) ? current($result) : null;
    }

    /**
     * Looks up all the LPA UIDs in the passed-in array.
     *
     * @param array $uids
     *
     * @return Response\LpaInterface[]
     * @throws Exception
     */
    public function lookup(array $uids): array
    {
        // Builds an array of Requests to send
        // The key for each request is the original uid.
        $signer   = ($this->requestSignerFactory)();
        $requests = array_combine(
            $uids,  // Use as array key
            array_map(function ($v) use ($signer) {
                $url     = $this->apiBaseUri . sprintf('/v1/use-an-lpa/lpas/%s', $v);
                $request = $this->requestFactory->createRequest('GET', $url);
                $request = $this->attachHeaders($request);

                return $signer->sign($request);
            }, $uids)
        );

        /**
         * Responses from the pool
         * @var ResponseInterface[] $results
         */
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
                    # TODO: We can some more error checking around this.
                    $results[$uid] = new Response\Lpa(
                        $this->sanitiser->sanitise(json_decode($result->getBody()->getContents(), true)),
                        new DateTimeImmutable($result->getHeaderLine('Date'))
                    );
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

        /** @var Response\LpaInterface[] */
        return $results;
    }

    /**
     * Contacts the api gateway and requests that Sirius send a new actor-code letter to the
     * $actorId that is attached to the LPA $caseId
     *
     * @link https://github.com/ministryofjustice/opg-data-lpa/blob/master/lambda_functions/v1/openapi/lpa-openapi.yml#L334
     *
     * @param int         $caseId  The Sirius uId of an LPA
     * @param int|null    $actorId The uId of an actor as found attached to an LPA
     * @param string|null $additionalInfo
     *
     * @return void
     * @throws ApiException
     */
    public function requestLetter(int $caseId, ?int $actorId, ?string $additionalInfo): void
    {
        $payloadContent = ['case_uid' => $caseId];

        if ($actorId === null) {
            $payloadContent['notes'] = $additionalInfo;
        } else {
            $payloadContent['actor_uid'] = $actorId;
        }

        // construct request for API gateway
        $url     = $this->apiBaseUri . '/v1/use-an-lpa/lpas/requestCode';
        $request = $this->requestFactory->createRequest('POST', $url);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payloadContent)));
        $request = $this->attachHeaders($request);
        $request = ($this->requestSignerFactory)()->sign($request);

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
}
