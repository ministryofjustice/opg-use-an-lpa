<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages as InstructionsAndPreferencesImagesDTO;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use App\Service\Log\RequestTracing;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Aws\Credentials\CredentialProvider;

class InstructionsAndPreferencesImages implements InstructionsAndPreferencesImagesInterface
{
    private string $apiBaseUri;

    /**
     * @param HttpClient $httpClient
     * @param RequestSigner $awsSignature
     * @param string $apiUrl
     * @param string $traceId An amazon trace id to pass to subsequent services
     */
    public function __construct(
        private HttpClient $httpClient,
        private RequestSigner $awsSignature,
        string $apiUrl,
        private string $traceId,
    ) {
        $this->apiBaseUri = $apiUrl;
    }

    /**
     * @param string $url
     * @return ResponseInterface
     * @throws ApiException
     */
    private function makeGetRequest(string $url): ResponseInterface
    {
        $url     = sprintf('%s/%s', $this->apiBaseUri, $url);
        $s4      = new SignatureV4('execute-api', 'eu-west-1');
        $request = new Request('GET', $url, $this->buildHeaders());

        $provider = CredentialProvider::defaultProvider();

        $request  = $s4->signRequest($request, $provider()->wait());

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $ge) {
            throw ApiException::create(
                'Error whilst communicating with instructions and preferences images service',
                null,
                $ge,
            );
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw ApiException::create(
                'Instructions and Preferences Images service returned non-ok response',
                $response,
            );
        }

        return $response;
    }

    public function getInstructionsAndPreferencesImages(int $lpaId): InstructionsAndPreferencesImagesDTO
    {
        $response = $this->makeGetRequest(
            'v1/image-request/' . $lpaId
        );

        $responseData = json_decode((string) $response->getBody());

        return new InstructionsAndPreferencesImagesDTO(
            (int) $responseData->uId,
            InstructionsAndPreferencesImagesResult::from((string) $responseData->status),
            (array) $responseData->signedUrls,
        );
    }

    /**
     * @return array{
     *     Accept: 'application/json',
     *     Content-Type: 'application/json',
     *     x-amzn-trace-id?: string,
     * }
     */
    private function buildHeaders(): array
    {
        $headerLines = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->traceId)) {
            $headerLines[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headerLines;
    }
}
