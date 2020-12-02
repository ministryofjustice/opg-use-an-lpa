<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response;
use App\Service\Log\RequestTracing;
use Aws\Credentials\CredentialProvider as AwsCredentialProvider;
use Aws\Signature\SignatureV4 as AwsSignatureV4;
use DateTime;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

/**
 * Looks up LPAs in the Sirius API Gateway.
 *
 * Class Lpas
 * @package App\DataAccess\ApiGateway
 */
class Lpas implements LpasInterface
{
    private HttpClient $httpClient;
    private string $apiBaseUri;
    private AwsSignatureV4 $awsSignature;
    private DataSanitiserStrategy $sanitiser;
    private string $traceId;

    public function __construct(
        HttpClient $httpClient,
        AwsSignatureV4 $awsSignature,
        string $apiUrl,
        string $traceId,
        DataSanitiserStrategy $sanitiser
    ) {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiUrl;
        $this->awsSignature = $awsSignature;
        $this->traceId = $traceId;
        $this->sanitiser = $sanitiser;
    }

    /**
     * Looks up an LPA based on its Sirius uid.
     *
     * @param string $uid
     * @return Response\LpaInterface|null
     * @throws \Exception
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
     * @throws \Exception
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

        //---

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
                    // We only care about 200s at the moment.
                    unset($results[$uid]);
            }
        }

        return $results;
    }

    private function buildHeaders(): array
    {
        $headerLines = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        if (!empty($this->traceId)) {
            $headerLines[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headerLines;
    }
}
