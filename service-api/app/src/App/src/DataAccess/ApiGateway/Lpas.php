<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response;
use App\Exception\ApiException;
use App\Service\Log\EventCodes;
use App\Service\Log\RequestTracing;
use Aws\Credentials\CredentialProvider as AwsCredentialProvider;
use Aws\Signature\SignatureV4 as AwsSignatureV4;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Looks up LPAs in the Sirius API Gateway.
 *
 * Class Lpas
 * @package App\DataAccess\ApiGateway
 */
class Lpas implements LpasInterface
{
    private string $apiBaseUri;
    private AwsSignatureV4 $awsSignature;
    private HttpClient $httpClient;
    private LoggerInterface $logger;
    private DataSanitiserStrategy $sanitiser;
    private string $traceId;

    public function __construct(
        HttpClient $httpClient,
        AwsSignatureV4 $awsSignature,
        string $apiUrl,
        string $traceId,
        DataSanitiserStrategy $sanitiser,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiUrl;
        $this->awsSignature = $awsSignature;
        $this->traceId = $traceId;
        $this->sanitiser = $sanitiser;
        $this->logger = $logger;
    }

    /**
     * Looks up an LPA based on its Sirius uid.
     *
     * @param string $uid
     * @return Response\LpaInterface|null
     * @throws Exception
     */
    public function get(string $uid): ?Response\LpaInterface
    {
        $result = $this->lookup([$uid]);
        return !empty($result) ? current($result) : null;
    }

    /**
     * Looks up the all the LPA uids in the passed array.
     *
     * @param array $uids
     * @return array
     * @throws Exception
     */
    public function lookup(array $uids): array
    {
        $provider = AwsCredentialProvider::defaultProvider();
        $credentials = $provider()->wait();

        // Builds an array of Requests to send
        // The key for each request is the original uid.
        $requests = array_combine(
            $uids,  // Use as array key
            array_map(function ($v) use ($credentials) {
                $url = $this->apiBaseUri . sprintf("/v1/use-an-lpa/lpas/%s", $v);

                $request = new Request('GET', $url, $this->buildHeaders());

                return $this->awsSignature->signRequest($request, $credentials);
            }, $uids)
        );

        //---

        // Responses from the pool
        $results = [];

        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 50,
            'options' => [
                'http_errors' => false,
            ],
            'fulfilled' => function ($response, $id) use (&$results) {
                $results[$id] = $response;
            },
            'rejected' => function ($reason, $id) {
                // Log?
            },
        ]);

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
                        $this->sanitiser->sanitise(json_decode((string)$result->getBody(), true)),
                        new DateTime($result->getHeaderLine('Date'))
                    );
                    break;
                default:
                    $this->logger->warning(
                        'Unexpected {status} response from gateway for request of LPA {lpaUid}',
                        [
                            'event_code' => EventCodes::UNEXPECTED_DATA_LPA_API_RESPONSE,
                            'status' => $statusCode,
                            'lpaUid' => $uid,
                        ]
                    );
                    unset($results[$uid]);
            }
        }

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
     * @return ResponseInterface
     */
    public function requestLetter(int $caseId, ?int $actorId, ?string $additionalInfo): void
    {
        $payloadContent = ['case_uid' => $caseId];

        if ($actorId === null) {
            $payloadContent['notes'] = $additionalInfo;
        } else {
            $payloadContent['actor_uid'] = $actorId;
        }

        $provider = AwsCredentialProvider::defaultProvider();
        $credentials = $provider()->wait();

        // request payload
        $body = json_encode($payloadContent);

        // construct request for API gateway
        $url = $this->apiBaseUri . '/v1/use-an-lpa/lpas/requestCode';
        $request = new Request('POST', $url, $this->buildHeaders(), $body);
        $request = $this->awsSignature->signRequest($request, $credentials);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $ge) {
            throw ApiException::create('Error whilst communicating with api gateway', null, $ge);
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

    private function buildHeaders(): array
    {
        $headerLines = [
            'Content-Type'  => 'application/json',
        ];

        if (!empty($this->traceId)) {
            $headerLines[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headerLines;
    }
}
