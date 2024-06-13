<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages as InstructionsAndPreferencesImagesDTO;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use App\Service\Log\RequestTracing;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Client\ClientInterface as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class InstructionsAndPreferencesImages implements InstructionsAndPreferencesImagesInterface
{
    private readonly RequestSigner $requestSigner;

    /**
     * @param HttpClient    $httpClient
     * @param RequestSignerFactory $requestSignerFactory
     * @param string        $apiBaseUri
     * @param string        $traceId An amazon trace id to pass to subsequent services
     */
    public function __construct(
        readonly HttpClient $httpClient,
        readonly RequestSignerFactory $requestSignerFactory,
        readonly private string $apiBaseUri,
        readonly private string $traceId,
    ) {
        $this->requestSigner = ($requestSignerFactory)();
    }

    /**
     * @param string $url
     * @return ResponseInterface
     * @throws ApiException
     */
    private function makeGetRequest(string $url): ResponseInterface
    {
        $url     = sprintf('%s/%s', $this->apiBaseUri, $url);
        $request = new Request('GET', $url, $this->buildHeaders());
        $request = $this->requestSigner->sign($request);

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